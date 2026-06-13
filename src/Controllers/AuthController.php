<?php

namespace Controllers;

use Core\{Request, Response};
use Models\{User, Establishment};
use Services\AuthService;

class AuthController
{
    public function login(Request $req, array $params = []): void
    {
        $email    = trim((string) $req->input('email', ''));
        $password = (string) $req->input('password', '');

        if (!$email || !$password) {
            Response::error('Email et mot de passe requis');
        }

        $user = User::findByEmail($email);
        if (!$user || !User::verifyPassword($password, $user['password_hash'])) {
            Response::error('Identifiants incorrects', 401);
        }

        $token = AuthService::encode([
            'id'               => $user['id'],
            'role'             => $user['role'],
            'name'             => $user['name'],
            'email'            => $user['email'],
            'establishment_id' => $user['establishment_id'],
        ]);

        $estabs = Establishment::forUser($user);

        Response::success([
            'token' => $token,
            'user'  => User::safe($user),
            'establishments' => $estabs,
        ], 'Connexion réussie');
    }

    public function logout(Request $req, array $params = []): void
    {
        // JWT is stateless — client just deletes the token
        Response::success(null, 'Déconnexion réussie');
    }

    public function register(Request $req, array $params = []): void
    {
        $data = $req->all();
        $required = ['name', 'email', 'password'];

        foreach ($required as $field) {
            if (empty($data[$field])) {
                Response::error("Champ requis : $field");
            }
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            Response::error('Email invalide');
        }

        if (User::findByEmail($data['email'])) {
            Response::error('Email déjà utilisé', 409);
        }

        if (strlen($data['password']) < 8) {
            Response::error('Mot de passe trop court (min. 8 caractères)');
        }

        // L'inscription publique crée uniquement des comptes "owner" sans
        // établissement pré-assigné. La création de réceptionnistes et le
        // rattachement à un établissement passent par un owner authentifié.
        $id = User::createUser([
            'role'             => 'owner',
            'name'             => $data['name'],
            'email'            => $data['email'],
            'password'         => $data['password'],
            'phone'            => $data['phone'] ?? null,
            'establishment_id' => null,
        ]);

        $user  = User::find($id);
        $token = AuthService::encode([
            'id'               => $user['id'],
            'role'             => $user['role'],
            'name'             => $user['name'],
            'email'            => $user['email'],
            'establishment_id' => $user['establishment_id'],
        ]);

        Response::success(['token' => $token, 'user' => User::safe($user)], 'Compte créé', 201);
    }

    public function me(Request $req, array $params = []): void
    {
        $payload = $_REQUEST['_user'];
        $user    = User::find($payload['id']);
        if (!$user) Response::unauthorized();
        Response::success(User::safe($user));
    }
}
