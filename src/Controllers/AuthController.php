<?php

namespace Controllers;

use Core\{Request, Response, RateLimiter, Database};
use Models\{User, Establishment};
use Services\{AuthService, MailService, WebauthnService, NotificationService};

class AuthController
{
    // ─── Gate PWA (WebAuthn/Passkey) ────────────────────────────────────────────
    // Contrainte produit : connexion réservée à l'app installée. Le mode
    // standalone n'étant pas vérifiable côté serveur, on exige à la place une
    // preuve cryptographique WebAuthn (passkey liée à l'appareil), émise lors
    // de la cérémonie d'enregistrement déclenchée à l'installation.

    public function webauthnRegisterOptions(Request $req, array $params = []): void
    {
        // Clé distincte de register-verify : ce endpoint ne fait que renvoyer un
        // défi (aucune donnée sensible créée) et est rappelé à chaque chargement
        // de /login tant qu'aucun device_token n'existe encore côté client — le
        // limiter au même seuil strict que les échecs de vérification bloquait
        // des sessions de test légitimes après quelques rechargements de page.
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'webauthn-register-options:' . $ip;

        if (RateLimiter::tooManyAttempts($key, 60, 3600)) {
            Response::error('Trop de tentatives.', 429);
        }
        RateLimiter::hit($key, 3600);

        Response::success(WebauthnService::registrationOptions());
    }

    public function webauthnRegisterVerify(Request $req, array $params = []): void
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'webauthn-register-verify:' . $ip;

        if (RateLimiter::tooManyAttempts($key, 20, 3600)) {
            Response::error('Trop de tentatives.', 429);
        }

        $state      = (string) $req->input('state', '');
        $credential = $req->input('credential');

        if (!$state || !is_array($credential)) {
            Response::error('Requête invalide');
        }

        try {
            $deviceToken = WebauthnService::verifyRegistration($state, $credential);
        } catch (\Throwable $e) {
            error_log('[AuthController] webauthnRegisterVerify échec — ' . get_class($e) . ' : ' . $e->getMessage());
            RateLimiter::hit($key, 3600);
            Response::error("Échec de l'enregistrement de l'appareil", 400);
        }

        // device_token : miné une seule fois ici (après une vraie cérémonie
        // WebAuthn), réutilisé silencieusement à chaque connexion ensuite —
        // cf. WebauthnService pour le raisonnement complet.
        Response::success(['device_token' => $deviceToken], 'Appareil enregistré');
    }

    public function login(Request $req, array $params = []): void
    {
        $email    = trim((string) $req->input('email', ''));
        $password = (string) $req->input('password', '');

        if (!$email || !$password) {
            Response::error('Email et mot de passe requis');
        }

        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'login:' . $ip . ':' . strtolower($email);

        if (RateLimiter::tooManyAttempts($key, 5, 900)) {
            Response::error('Trop de tentatives. Réessayez dans quelques minutes.', 429);
        }

        $user = User::findByEmail($email);
        if (!$user || !User::verifyPassword($password, $user['password_hash'])) {
            RateLimiter::hit($key, 900);
            Response::error('Identifiants incorrects', 401);
        }

        RateLimiter::clear($key);

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
        // Le token est explicitement révoqué (liste noire en base) : un jeton
        // volé avant le logout ne peut plus être rejoué après.
        $token = $req->bearerToken();
        if ($token) {
            $payload = AuthService::decode($token);
            if ($payload && isset($payload['jti'], $payload['exp'])) {
                AuthService::revoke($payload['jti'], (int) $payload['exp']);
            }
        }
        Response::success(null, 'Déconnexion réussie');
    }

    public function register(Request $req, array $params = []): void
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'register:' . $ip;

        if (RateLimiter::tooManyAttempts($key, 5, 3600)) {
            Response::error('Trop de tentatives. Réessayez plus tard.', 429);
        }

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

        RateLimiter::hit($key, 3600);

        if (User::findByEmail($data['email'])) {
            Response::error('Email déjà utilisé', 409);
        }

        if (strlen($data['password']) < 8) {
            Response::error('Mot de passe trop court (min. 8 caractères)');
        }
        if (!preg_match('/[a-zA-Z]/', $data['password']) || !preg_match('/\d/', $data['password'])) {
            Response::error('Le mot de passe doit contenir au moins une lettre et un chiffre');
        }

        $userId = User::createUser([
            'role'             => 'owner',
            'name'             => $data['name'],
            'email'            => $data['email'],
            'password'         => $data['password'],
            'phone'            => $data['phone'] ?? null,
            'establishment_id' => null,
        ]);

        // Créer l'établissement si le nom est fourni (étape 2 du formulaire)
        $estabName = trim($data['establishment_name'] ?? '');
        $estabType = in_array($data['establishment_type'] ?? '', ['hotel', 'residence'])
                     ? $data['establishment_type']
                     : 'hotel';

        $estabId = null;
        if ($estabName !== '') {
            $estabId = Establishment::create([
                'owner_id' => $userId,
                'name'     => $estabName,
                'type'     => $estabType,
                'plan'     => 'starter',
            ]);

            // Relier l'utilisateur à son établissement
            User::update($userId, ['establishment_id' => $estabId]);

            NotificationService::newEstablishment($estabName, $data['name'], $estabId);
        }

        $user  = User::find($userId);
        $token = AuthService::encode([
            'id'               => $user['id'],
            'role'             => $user['role'],
            'name'             => $user['name'],
            'email'            => $user['email'],
            'establishment_id' => $user['establishment_id'],
        ]);

        $estabs = Establishment::forUser($user);

        // Email de bienvenue (non bloquant)
        MailService::welcome($user['email'], $user['name'], $estabName ?: '');

        Response::success([
            'token'          => $token,
            'user'           => User::safe($user),
            'establishments' => $estabs,
        ], 'Compte créé', 201);
    }

    public function me(Request $req, array $params = []): void
    {
        $payload = $_REQUEST['_user'];
        $user    = User::find($payload['id']);
        if (!$user) Response::unauthorized();
        Response::success(User::safe($user));
    }

    // ─── Mot de passe oublié ────────────────────────────────────────────────────

    public function forgotPassword(Request $req, array $params = []): void
    {
        $email = trim((string) $req->input('email', ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::error('Email invalide');
        }

        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'forgot-password:' . $ip . ':' . strtolower($email);
        if (RateLimiter::tooManyAttempts($key, 5, 3600)) {
            Response::error('Trop de tentatives. Réessayez plus tard.', 429);
        }
        RateLimiter::hit($key, 3600);

        $user = User::findByEmail($email);
        if ($user) {
            $token = bin2hex(random_bytes(32));

            // Un seul lien de réinitialisation valide à la fois par compte.
            Database::query('DELETE FROM password_resets WHERE user_id = ? AND used_at IS NULL', [$user['id']]);
            Database::query(
                'INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, ?)',
                [$user['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + 3600)]
            );

            $resetUrl = rtrim(APP_URL, '/') . '/reset-password?token=' . $token;
            MailService::passwordReset($user['email'], $user['name'], $resetUrl);
        }

        // Message générique dans tous les cas : ne révèle pas si l'email existe.
        Response::success(null, 'Si un compte existe pour cet email, un lien de réinitialisation a été envoyé.');
    }

    public function resetPassword(Request $req, array $params = []): void
    {
        $token    = (string) $req->input('token', '');
        $password = (string) $req->input('password', '');

        if (!$token || !$password) {
            Response::error('Champs requis manquants');
        }

        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'reset-password:' . $ip;
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
            'SELECT * FROM password_resets WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()',
            [hash('sha256', $token)]
        )->fetch();

        if (!$row) {
            Response::error('Lien invalide ou expiré', 400);
        }

        User::update((int) $row['user_id'], ['password_hash' => User::hashPassword($password)]);
        Database::query('UPDATE password_resets SET used_at = NOW() WHERE id = ?', [$row['id']]);
        // Les autres liens en attente pour ce compte sont invalidés (un seul reset valide à la fois).
        Database::query(
            'UPDATE password_resets SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL',
            [$row['user_id']]
        );

        Response::success(null, 'Mot de passe réinitialisé. Vous pouvez maintenant vous connecter.');
    }
}
