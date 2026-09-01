<?php

namespace Controllers;

use Core\{Request, Response, RateLimiter, Database};
use Models\{User, Establishment};
use Services\{AuthService, MailService, WebauthnService, WebauthnLoginService, NotificationService, UploadService};

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

        // Suspension manuelle par le superadmin (AdminController::suspendOwner) —
        // distincte du gel d'établissement, bloque la connexion elle-même.
        if (!empty($user['suspended_at'])) {
            Response::error('Ce compte a été suspendu. Contactez le support.', 403);
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

    // ─── Connexion optionnelle par empreinte digitale (WebAuthn/passkey) ───────
    // Raccourci FACULTATIF en plus du mot de passe, jamais un remplacement ni
    // une obligation — à ne pas confondre avec le gate "app installée"
    // ci-dessus (WebauthnService, anonyme). Voir WebauthnLoginService.

    /** Depuis Paramètres, utilisateur déjà connecté. */
    public function webauthnLoginCredentialRegisterOptions(Request $req, array $params = []): void
    {
        $user = $_REQUEST['_user'];
        Response::success(WebauthnLoginService::registrationOptions(
            (int) $user['id'],
            (string) ($user['email'] ?? ''),
            (string) ($user['name'] ?? '')
        ));
    }

    public function webauthnLoginCredentialRegisterVerify(Request $req, array $params = []): void
    {
        $user = $_REQUEST['_user'];

        $state      = (string) $req->input('state', '');
        $credential = $req->input('credential');

        if (!$state || !is_array($credential)) {
            Response::error('Requête invalide');
        }

        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255);

        try {
            $id = WebauthnLoginService::verifyRegistration($state, $credential, (int) $user['id'], AuthService::deviceLabel($ua));
        } catch (\Throwable $e) {
            error_log('[AuthController] webauthnLoginCredentialRegisterVerify échec — ' . get_class($e) . ' : ' . $e->getMessage());
            Response::error("Échec de l'enregistrement de l'empreinte", 400);
        }

        Response::success(['id' => $id], 'Empreinte activée sur cet appareil');
    }

    public function webauthnLoginCredentials(Request $req, array $params = []): void
    {
        $user = $_REQUEST['_user'];
        Response::success(WebauthnLoginService::listForUser((int) $user['id']));
    }

    public function webauthnLoginCredentialRevoke(Request $req, array $params = []): void
    {
        $user = $_REQUEST['_user'];
        $id   = (int) ($params['id'] ?? $_GET['_route_id'] ?? 0);

        if (!WebauthnLoginService::revoke($id, (int) $user['id'])) {
            Response::error('Empreinte introuvable', 404);
        }
        Response::success(null, 'Empreinte désactivée');
    }

    /** Public — c'est le moyen de se connecter, pas une action après connexion. */
    public function webauthnLoginOptions(Request $req, array $params = []): void
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'webauthn-login-options:' . $ip;

        if (RateLimiter::tooManyAttempts($key, 30, 900)) {
            Response::error('Trop de tentatives.', 429);
        }
        RateLimiter::hit($key, 900);

        Response::success(WebauthnLoginService::loginOptions());
    }

    public function webauthnLoginVerify(Request $req, array $params = []): void
    {
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'webauthn-login-verify:' . $ip;

        if (RateLimiter::tooManyAttempts($key, 10, 900)) {
            Response::error('Trop de tentatives. Réessayez dans quelques minutes.', 429);
        }

        $state      = (string) $req->input('state', '');
        $credential = $req->input('credential');

        if (!$state || !is_array($credential)) {
            Response::error('Requête invalide');
        }

        $userId = WebauthnLoginService::verifyLogin($state, $credential);
        if (!$userId) {
            RateLimiter::hit($key, 900);
            Response::error('Échec de la connexion par empreinte', 401);
        }

        $user = User::find($userId);
        if (!$user) {
            Response::error('Compte introuvable', 401);
        }

        RateLimiter::clear($key);

        // Même forme de réponse que login() (mot de passe) — le frontend réutilise
        // sa logique de stockage/redirection sans distinction entre les deux.
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
                'city'     => trim($data['establishment_city'] ?? '') ?: null,
                'plan'     => 'starter',
            ]);

            Establishment::update($estabId, ['slug' => Establishment::generateSlug($estabName, $estabId)]);

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
        self::sendVerificationEmail($user);

        Response::success([
            'token'          => $token,
            'user'           => User::safe($user),
            'establishments' => $estabs,
        ], 'Compte créé', 201);
    }

    // ─── Vérification d'email ───────────────────────────────────────────────────
    // Ne bloque jamais la connexion (choix produit : friction au setup, pas à
    // chaque usage) — simple rappel côté SaaS tant que non confirmé.

    private static function sendVerificationEmail(array $user): void
    {
        $token = bin2hex(random_bytes(32));

        Database::query('DELETE FROM email_verifications WHERE user_id = ? AND used_at IS NULL', [$user['id']]);
        Database::query(
            'INSERT INTO email_verifications (user_id, token_hash, expires_at) VALUES (?, ?, ?)',
            [$user['id'], hash('sha256', $token), date('Y-m-d H:i:s', time() + 86400)]
        );

        $verifyUrl = rtrim(APP_URL, '/') . '/verify-email?token=' . $token;
        MailService::verifyEmail($user['email'], $user['name'], $verifyUrl);
    }

    public function verifyEmail(Request $req, array $params = []): void
    {
        $token = (string) $req->input('token', '');
        if (!$token) {
            Response::error('Jeton manquant');
        }

        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'verify-email:' . $ip;
        if (RateLimiter::tooManyAttempts($key, 20, 3600)) {
            Response::error('Trop de tentatives. Réessayez plus tard.', 429);
        }
        RateLimiter::hit($key, 3600);

        $row = Database::query(
            'SELECT * FROM email_verifications WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()',
            [hash('sha256', $token)]
        )->fetch();

        if (!$row) {
            Response::error('Lien invalide ou expiré', 400);
        }

        Database::query('UPDATE users SET email_verified_at = NOW() WHERE id = ?', [$row['user_id']]);
        Database::query('UPDATE email_verifications SET used_at = NOW() WHERE id = ?', [$row['id']]);

        Response::success(null, 'Email confirmé avec succès.');
    }

    public function resendVerification(Request $req, array $params = []): void
    {
        $payload = $_REQUEST['_user'];
        $user    = User::find($payload['id']);
        if (!$user) Response::unauthorized();

        if (!empty($user['email_verified_at'])) {
            Response::success(null, 'Cet email est déjà confirmé.');
        }

        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'resend-verification:' . $ip . ':' . $user['id'];
        if (RateLimiter::tooManyAttempts($key, 3, 3600)) {
            Response::error('Trop de tentatives. Réessayez plus tard.', 429);
        }
        RateLimiter::hit($key, 3600);

        self::sendVerificationEmail($user);

        Response::success(null, 'Email de confirmation renvoyé.');
    }

    // ─── Changement de mot de passe (connecté) ──────────────────────────────────
    public function changePassword(Request $req, array $params = []): void
    {
        $payload = $_REQUEST['_user'];
        $user    = User::find((int) ($payload['id'] ?? 0));
        if (!$user) Response::unauthorized();

        $current = (string) $req->input('current_password', '');
        $new     = (string) $req->input('new_password', '');
        if (!$current || !$new) {
            Response::error('Champs requis manquants');
        }

        $key = 'change-password:' . (int) $user['id'];
        if (RateLimiter::tooManyAttempts($key, 5, 3600)) {
            Response::error('Trop de tentatives. Réessayez plus tard.', 429);
        }
        RateLimiter::hit($key, 3600);

        if (!User::verifyPassword($current, $user['password_hash'])) {
            Response::error('Mot de passe actuel incorrect', 403);
        }
        if ($new === $current) {
            Response::error('Le nouveau mot de passe doit être différent de l\'ancien');
        }
        if (strlen($new) < 8) {
            Response::error('Le nouveau mot de passe doit contenir au moins 8 caractères');
        }
        if (!preg_match('/[a-zA-Z]/', $new) || !preg_match('/\d/', $new)) {
            Response::error('Le nouveau mot de passe doit contenir au moins une lettre et un chiffre');
        }

        User::update((int) $user['id'], ['password_hash' => User::hashPassword($new)]);

        Response::success(null, 'Mot de passe modifié avec succès.');
    }

    public function me(Request $req, array $params = []): void
    {
        $payload = $_REQUEST['_user'];
        $user    = User::find($payload['id']);
        if (!$user) Response::unauthorized();
        Response::success(User::safe($user));
    }

    // ─── Profil (onglet Compte) ─────────────────────────────────────────────────
    // Volontairement séparé de changePassword()/uploadAvatar() : trois actions
    // indépendantes, chacune avec sa propre validation, plutôt qu'un gros
    // formulaire "tout ou rien".

    public function updateProfile(Request $req, array $params = []): void
    {
        $payload = $_REQUEST['_user'];
        $user    = User::find((int) ($payload['id'] ?? 0));
        if (!$user) Response::unauthorized();

        $name  = trim((string) $req->input('name', ''));
        $phone = trim((string) $req->input('phone', ''));

        if ($name === '') {
            Response::error('Le nom est requis');
        }
        if (mb_strlen($name) > 150) {
            Response::error('Nom trop long (150 caractères max)');
        }

        User::update((int) $user['id'], [
            'name'  => $name,
            'phone' => $phone !== '' ? $phone : null,
        ]);

        Response::success(User::safe(User::find((int) $user['id'])), 'Profil mis à jour');
    }

    public function uploadAvatar(Request $req, array $params = []): void
    {
        $payload = $_REQUEST['_user'];
        $user    = User::find((int) ($payload['id'] ?? 0));
        if (!$user) Response::unauthorized();

        $file = $req->file('avatar');
        if (!$file) Response::error('Aucun fichier reçu');

        try {
            $path = UploadService::uploadAvatar($file, (int) $user['id']);
        } catch (\InvalidArgumentException $e) {
            Response::error($e->getMessage());
        }

        // Ancien fichier supprimé après upload du nouveau (jamais avant : un
        // upload en échec ne doit pas laisser l'utilisateur sans avatar).
        if (!empty($user['avatar_path'])) {
            $old = UPLOAD_PATH . '/../' . $user['avatar_path'];
            if (file_exists($old)) @unlink($old);
        }

        User::update((int) $user['id'], ['avatar_path' => $path]);

        Response::success(['avatar_path' => $path], 'Photo de profil mise à jour');
    }

    public function deleteAvatar(Request $req, array $params = []): void
    {
        $payload = $_REQUEST['_user'];
        $user    = User::find((int) ($payload['id'] ?? 0));
        if (!$user) Response::unauthorized();

        if (!empty($user['avatar_path'])) {
            $old = UPLOAD_PATH . '/../' . $user['avatar_path'];
            if (file_exists($old)) @unlink($old);
            User::update((int) $user['id'], ['avatar_path' => null]);
        }

        Response::success(null, 'Photo de profil supprimée');
    }

    // ─── Appareils connectés (sessions actives + historique de connexion) ──────
    // Basé sur user_sessions, alimenté par AuthService::encode()/revoke() —
    // pas de table séparée pour l'historique, une session expirée/révoquée
    // reste visible (purgée après 90 jours) et sert d'historique.

    public function sessions(Request $req, array $params = []): void
    {
        $payload    = $_REQUEST['_user'];
        $currentJti = $payload['jti'] ?? null;

        $rows = Database::query(
            'SELECT id, jti, ip_address, device_label, created_at, expires_at, revoked_at
             FROM user_sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20',
            [(int) $payload['id']]
        )->fetchAll();

        $sessions = array_map(function ($row) use ($currentJti) {
            $row['is_current'] = ($row['jti'] === $currentJti);
            $row['is_active']  = $row['revoked_at'] === null && strtotime($row['expires_at']) > time();
            unset($row['jti']);
            return $row;
        }, $rows);

        Response::success($sessions);
    }

    public function revokeSession(Request $req, array $params = []): void
    {
        $payload = $_REQUEST['_user'];
        $id      = (int) ($params['id'] ?? 0);

        $session = Database::query('SELECT * FROM user_sessions WHERE id = ?', [$id])->fetch();
        if (!$session || (int) $session['user_id'] !== (int) $payload['id']) {
            Response::notFound('Session introuvable');
        }

        if ($session['revoked_at'] === null) {
            AuthService::revoke($session['jti'], strtotime($session['expires_at']));
        }

        Response::success(null, 'Appareil déconnecté');
    }

    public function revokeOtherSessions(Request $req, array $params = []): void
    {
        $payload    = $_REQUEST['_user'];
        $currentJti = $payload['jti'] ?? null;

        $rows = Database::query(
            "SELECT jti, expires_at FROM user_sessions
             WHERE user_id = ? AND revoked_at IS NULL AND expires_at > NOW() AND jti != ?",
            [(int) $payload['id'], (string) $currentJti]
        )->fetchAll();

        foreach ($rows as $row) {
            AuthService::revoke($row['jti'], strtotime($row['expires_at']));
        }

        Response::success(null, count($rows) . ' appareil(s) déconnecté(s)');
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
