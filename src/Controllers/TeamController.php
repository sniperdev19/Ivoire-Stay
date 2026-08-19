<?php

namespace Controllers;

use Core\{Request, Response, Guard, Database, RateLimiter};
use Models\{User, Establishment};
use Services\{NotificationService, MailService};

/**
 * Gestion des membres d'équipe (rôle `receptionist`) d'un établissement.
 * Réservé aux owner/superadmin (cf. ACCES_PROFILS_ABONNEMENT.md) — un membre
 * ne peut ni créer ni gérer d'autres membres.
 *
 * Inscription par invitation : l'owner ne saisit que l'email, un lien à usage
 * unique (24h) est envoyé, le réceptionniste choisit lui-même son nom et son
 * mot de passe en l'acceptant (cf. invite()/acceptInvite(), même pattern que
 * email_verifications/password_resets — token_hash sha256, expires_at).
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

        $invitations = Database::query(
            "SELECT id, email, establishment_id, expires_at, created_at
             FROM team_invitations
             WHERE establishment_id = ? AND accepted_at IS NULL AND expires_at > NOW()
             ORDER BY created_at DESC",
            [$estabId]
        )->fetchAll();

        Response::success([
            'members'     => array_map(fn($u) => User::safe($u), $members),
            'invitations' => $invitations,
        ]);
    }

    /**
     * Envoie une invitation par email. Renvoyer une invitation vers le même
     * email/établissement invalide simplement le lien précédent (un seul lien
     * actif à la fois), donc réutilisée telle quelle pour "renvoyer" côté UI.
     */
    public function invite(Request $req, array $params = []): void
    {
        $data    = $req->all();
        $estabId = $this->estabId($req);
        if (!$estabId) Response::error('establishment_id requis');

        $email = trim((string) ($data['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Email invalide');
        }
        if (User::findByEmail($email)) {
            Response::error('Cet email est déjà utilisé par un compte existant', 409);
        }

        $estab = Establishment::find($estabId);
        if (!$estab) Response::error('Établissement introuvable', 404);

        Database::query(
            'DELETE FROM team_invitations WHERE email = ? AND establishment_id = ? AND accepted_at IS NULL',
            [$email, $estabId]
        );

        $token = bin2hex(random_bytes(32));
        Database::query(
            'INSERT INTO team_invitations (establishment_id, email, invited_by, token_hash, expires_at) VALUES (?, ?, ?, ?, ?)',
            [$estabId, $email, (int) (Guard::user()['id'] ?? 0), hash('sha256', $token), date('Y-m-d H:i:s', time() + 86400)]
        );

        $inviteUrl = rtrim(APP_URL, '/') . '/team-invite?token=' . $token;
        MailService::teamInvitation($email, (string) ($estab['name'] ?? ''), $inviteUrl);

        Response::success(null, 'Invitation envoyée à ' . $email, 201);
    }

    public function cancelInvite(Request $req, array $params = []): void
    {
        $id = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);

        $row = Database::query('SELECT * FROM team_invitations WHERE id = ?', [$id])->fetch();
        if (!$row) Response::notFound('Invitation introuvable');
        Guard::requireEstablishment((int) $row['establishment_id']);

        Database::query('DELETE FROM team_invitations WHERE id = ?', [$id]);
        Response::success(null, 'Invitation annulée');
    }

    // ─── Acceptation publique (aucune authentification requise) ─────────────

    public function inviteInfo(Request $req, array $params = []): void
    {
        $token = (string) $req->get('token', '');
        if (!$token) Response::error('Jeton manquant');

        $row = Database::query(
            "SELECT ti.email, e.name AS establishment_name
             FROM team_invitations ti
             JOIN establishments e ON e.id = ti.establishment_id
             WHERE ti.token_hash = ? AND ti.accepted_at IS NULL AND ti.expires_at > NOW()",
            [hash('sha256', $token)]
        )->fetch();

        if (!$row) Response::error('Invitation invalide ou expirée', 404);

        Response::success($row);
    }

    public function acceptInvite(Request $req, array $params = []): void
    {
        $token    = (string) $req->input('token', '');
        $name     = trim((string) $req->input('name', ''));
        $password = (string) $req->input('password', '');

        if (!$token || !$name || !$password) {
            Response::error('Champs requis manquants');
        }

        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'team-invite-accept:' . $ip;
        if (RateLimiter::tooManyAttempts($key, 10, 3600)) {
            Response::error('Trop de tentatives. Réessayez plus tard.', 429);
        }
        RateLimiter::hit($key, 3600);

        if (strlen($password) < 8) {
            Response::error('Mot de passe trop court (min. 8 caractères)');
        }
        if (!preg_match('/[a-zA-Z]/', $password) || !preg_match('/\d/', $password)) {
            Response::error('Le mot de passe doit contenir au moins une lettre et un chiffre');
        }

        $row = Database::query(
            'SELECT * FROM team_invitations WHERE token_hash = ? AND accepted_at IS NULL AND expires_at > NOW()',
            [hash('sha256', $token)]
        )->fetch();
        if (!$row) Response::error('Invitation invalide ou expirée', 400);

        if (User::findByEmail($row['email'])) {
            Response::error('Cet email est déjà utilisé par un compte existant', 409);
        }

        $userId = User::createUser([
            'role'             => 'receptionist',
            'name'             => $name,
            'email'            => $row['email'],
            'password'         => $password,
            'establishment_id' => $row['establishment_id'],
        ]);

        Database::query('UPDATE team_invitations SET accepted_at = NOW() WHERE id = ?', [$row['id']]);

        NotificationService::teamMemberAdded((int) $row['establishment_id'], $name);

        Response::success(User::safe(User::find($userId)), 'Compte créé', 201);
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
