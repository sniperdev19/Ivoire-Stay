<?php

namespace Controllers;

use Core\{Request, Response, Guard};
use Models\User;
use Services\NotificationService;

/**
 * Gestion des membres d'équipe (rôle `receptionist`) d'un établissement.
 * Réservé aux owner/superadmin (cf. ACCES_PROFILS_ABONNEMENT.md) — un membre
 * ne peut ni créer ni gérer d'autres membres.
 */
class TeamController
{
    private function estabId(Request $req): int
    {
        return Guard::resolveEstabId($req);
    }

    public function index(Request $req, array $params = []): void
    {
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');

        $members = array_values(array_filter(
            User::findByEstablishment($estabId),
            fn($u) => $u['role'] === 'receptionist'
        ));
        Response::success(array_map(fn($u) => User::safe($u), $members));
    }

    public function store(Request $req, array $params = []): void
    {
        $data    = $req->all();
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');

        $required = ['name', 'email', 'password'];
        foreach ($required as $f) {
            if (empty($data[$f])) Response::error("Champ requis : $f");
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Email invalide');
        }
        if (User::findByEmail($data['email'])) {
            Response::error('Cet email est déjà utilisé', 409);
        }
        if (strlen($data['password']) < 8) {
            Response::error('Mot de passe trop court (min. 8 caractères)');
        }

        $id = User::createUser([
            'role'             => 'receptionist',
            'name'             => $data['name'],
            'email'            => $data['email'],
            'password'         => $data['password'],
            'phone'            => $data['phone'] ?? null,
            'establishment_id' => $estabId,
        ]);

        NotificationService::teamMemberAdded($estabId, $data['name']);

        Response::success(User::safe(User::find($id)), 'Membre créé', 201);
    }

    public function update(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireTeamMember($id);

        $data   = $req->all();
        $update = array_intersect_key($data, array_flip(['name', 'phone']));

        if (!empty($data['password'])) {
            if (strlen($data['password']) < 8) Response::error('Mot de passe trop court (min. 8 caractères)');
            $update['password_hash'] = User::hashPassword($data['password']);
        }

        if (!empty($update)) User::update($id, $update);
        Response::success(User::safe(User::find($id)), 'Membre mis à jour');
    }

    public function destroy(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);
        Guard::requireTeamMember($id);
        User::delete($id);
        Response::success(null, 'Membre supprimé');
    }
}
