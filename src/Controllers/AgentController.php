<?php

namespace Controllers;

use Core\{Request, Response, RateLimiter};
use Models\{Agent, AgentEstablishment, AgentReferral, AgentPayout, AgentBonusAward, AgentProspect, Establishment};
use Services\{AuthService, CommissionService};

/**
 * Espace agents commerciaux — fonctionnalité temporaire (cf. AGENTS_ENABLED
 * dans config/config.php). Les agents ne sont PAS des `users` : pas
 * d'établissement propre, connexion par numéro de téléphone. Ils vivent dans
 * leur propre table `agents`, avec un jeton JWT dédié (payload sans clé `id`,
 * donc sans impact sur AuthService::recordSession() qui suppose un `users.id`).
 */
class AgentController
{
    private const OPERATORS = ['orange', 'mtn', 'moov', 'wave'];

    private function guardEnabled(): void
    {
        if (!AGENTS_ENABLED) Response::notFound('Fonctionnalité indisponible');
    }

    private function isValidCiPhone(string $v): bool
    {
        $digits = preg_replace('/\D/', '', $v);
        if (str_starts_with($digits, '225')) $digits = substr($digits, 3);
        return strlen($digits) === 10 && preg_match('/^(01|05|07)/', $digits) === 1;
    }

    private function issueToken(array $agent): string
    {
        return AuthService::encode([
            'role'     => 'agent',
            'agent_id' => $agent['id'],
            'nom'      => $agent['nom'],
            'numero'   => $agent['numero'],
        ]);
    }

    public function register(Request $req, array $params = []): void
    {
        $this->guardEnabled();

        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'agent-register:' . $ip;
        if (RateLimiter::tooManyAttempts($key, 5, 3600)) {
            Response::error('Trop de tentatives. Réessayez plus tard.', 429);
        }

        $data = $req->all();
        foreach (['nom', 'numero', 'operateur_money', 'password'] as $field) {
            if (empty($data[$field])) Response::error("Champ requis : $field");
        }

        if (!$this->isValidCiPhone((string) $data['numero'])) {
            Response::error('Numéro invalide : 10 chiffres commençant par 01, 05 ou 07');
        }
        if (!in_array($data['operateur_money'], self::OPERATORS, true)) {
            Response::error('Opérateur Mobile Money invalide');
        }
        if (strlen($data['password']) < 8) {
            Response::error('Mot de passe trop court (min. 8 caractères)');
        }
        if (!preg_match('/[a-zA-Z]/', $data['password']) || !preg_match('/\d/', $data['password'])) {
            Response::error('Le mot de passe doit contenir au moins une lettre et un chiffre');
        }

        RateLimiter::hit($key, 3600);

        $numero = preg_replace('/\D/', '', $data['numero']);
        if (str_starts_with($numero, '225')) $numero = substr($numero, 3);

        if (Agent::findByNumero($numero)) {
            Response::error('Ce numéro est déjà inscrit', 409);
        }

        $agentId = Agent::createAgent([
            'nom'             => trim($data['nom']),
            'numero'          => $numero,
            'operateur_money' => $data['operateur_money'],
            'password'        => $data['password'],
        ]);

        $agent = Agent::find($agentId);

        Response::success([
            'token' => $this->issueToken($agent),
            'agent' => Agent::safe($agent),
        ], 'Compte agent créé', 201);
    }

    public function login(Request $req, array $params = []): void
    {
        $this->guardEnabled();

        $numero   = preg_replace('/\D/', '', (string) $req->input('numero', ''));
        $password = (string) $req->input('password', '');
        if (str_starts_with($numero, '225')) $numero = substr($numero, 3);

        if (!$numero || !$password) {
            Response::error('Numéro et mot de passe requis');
        }

        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'agent-login:' . $ip . ':' . $numero;
        if (RateLimiter::tooManyAttempts($key, 5, 900)) {
            Response::error('Trop de tentatives. Réessayez dans quelques minutes.', 429);
        }

        $agent = Agent::findByNumero($numero);
        if (!$agent || !Agent::verifyPassword($password, $agent['password_hash'])) {
            RateLimiter::hit($key, 900);
            Response::error('Identifiants incorrects', 401);
        }

        RateLimiter::clear($key);

        Response::success([
            'token' => $this->issueToken($agent),
            'agent' => Agent::safe($agent),
        ], 'Connexion réussie');
    }

    public function logout(Request $req, array $params = []): void
    {
        $token = $req->bearerToken();
        if ($token) {
            $payload = AuthService::decode($token);
            if ($payload && isset($payload['jti'], $payload['exp'])) {
                AuthService::revoke($payload['jti'], (int) $payload['exp']);
            }
        }
        Response::success(null, 'Déconnexion réussie');
    }

    /** Scan du QR affiché dans les paramètres d'un établissement — rattache l'agent connecté. */
    public function scanQr(Request $req, array $params = []): void
    {
        $this->guardEnabled();

        $qrToken = trim((string) $req->input('qr_token', ''));
        if (!$qrToken) Response::error('QR code invalide');

        $estab = Establishment::findByQrToken($qrToken);
        if (!$estab) Response::notFound('QR code invalide ou établissement introuvable');

        $agentId = (int) ($_REQUEST['_user']['agent_id'] ?? 0);

        $existing = AgentEstablishment::findByEstablishment((int) $estab['id']);
        if ($existing) {
            if ((int) $existing['agent_id'] === $agentId) {
                Response::success(['establishment_name' => $estab['name']], 'Cet établissement est déjà rattaché à votre compte');
            }
            Response::error('Cet établissement est déjà rattaché à un autre agent', 409);
        }

        AgentEstablishment::create([
            'agent_id'         => $agentId,
            'establishment_id' => (int) $estab['id'],
        ]);

        // Établissement déjà sur un plan payant au moment du scan (pas rattaché
        // via un futur upgrade) : crédite quand même l'agent immédiatement,
        // plutôt que d'exiger que l'upgrade ait lieu après le rattachement.
        // recordFirstSubscription() est idempotent (vérifie qu'aucune ligne
        // agent_referrals n'existe déjà pour cet établissement), donc un futur
        // renouvellement/upgrade ne recréditera pas une seconde fois.
        CommissionService::recordFirstSubscription((int) $estab['id'], (string) $estab['plan']);

        Response::success(['establishment_name' => $estab['name']], 'Établissement rattaché avec succès', 201);
    }

    /** PUT /api/agent/profile — mise à jour nom / numéro / opérateur Mobile Money. */
    public function updateProfile(Request $req, array $params = []): void
    {
        $this->guardEnabled();

        $agentId = (int) ($_REQUEST['_user']['agent_id'] ?? 0);
        $agent   = Agent::find($agentId);
        if (!$agent) Response::unauthorized();

        $nom       = trim((string) $req->input('nom', ''));
        $numero    = preg_replace('/\D/', '', (string) $req->input('numero', ''));
        if (str_starts_with($numero, '225')) $numero = substr($numero, 3);
        $operateur = (string) $req->input('operateur_money', '');

        if ($nom === '') Response::error('Le nom est requis');
        if (mb_strlen($nom) > 150) Response::error('Nom trop long (150 caractères max)');
        if (!$this->isValidCiPhone($numero)) {
            Response::error('Numéro invalide : 10 chiffres commençant par 01, 05 ou 07');
        }
        if (!in_array($operateur, self::OPERATORS, true)) {
            Response::error('Opérateur Mobile Money invalide');
        }

        // Numéro = identifiant de connexion (AuthController::login cherche par
        // numero) : unique, comme à l'inscription.
        if ($numero !== $agent['numero']) {
            $existing = Agent::findByNumero($numero);
            if ($existing && (int) $existing['id'] !== $agentId) {
                Response::error('Ce numéro est déjà utilisé par un autre compte agent', 409);
            }
        }

        Agent::update($agentId, [
            'nom'             => $nom,
            'numero'          => $numero,
            'operateur_money' => $operateur,
        ]);

        Response::success(Agent::safe(Agent::find($agentId)), 'Profil mis à jour');
    }

    /** POST /api/agent/change-password */
    public function changePassword(Request $req, array $params = []): void
    {
        $this->guardEnabled();

        $agentId = (int) ($_REQUEST['_user']['agent_id'] ?? 0);
        $agent   = Agent::find($agentId);
        if (!$agent) Response::unauthorized();

        $current = (string) $req->input('current_password', '');
        $new     = (string) $req->input('new_password', '');
        if (!$current || !$new) {
            Response::error('Champs requis manquants');
        }

        $key = 'agent-change-password:' . $agentId;
        if (RateLimiter::tooManyAttempts($key, 5, 3600)) {
            Response::error('Trop de tentatives. Réessayez plus tard.', 429);
        }
        RateLimiter::hit($key, 3600);

        if (!Agent::verifyPassword($current, $agent['password_hash'])) {
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

        Agent::update($agentId, ['password_hash' => Agent::hashPassword($new)]);

        Response::success(null, 'Mot de passe modifié avec succès.');
    }

    /** GET /api/agent/me — tableau de bord de l'agent connecté. */
    public function me(Request $req, array $params = []): void
    {
        $this->guardEnabled();

        $agentId = (int) ($_REQUEST['_user']['agent_id'] ?? 0);
        $agent   = Agent::find($agentId);
        if (!$agent) Response::notFound('Agent introuvable');

        Response::success([
            'agent'          => Agent::safe($agent),
            'establishments' => AgentEstablishment::forAgent($agentId),
            'referrals'      => AgentReferral::forAgent($agentId),
            'payouts'        => AgentPayout::findByAgent($agentId),
            'bonusAwards'    => AgentBonusAward::forAgent($agentId),
            'progress'       => [
                'pro'      => $this->batchProgress($agentId, 'pro'),
                'business' => $this->batchProgress($agentId, 'business'),
            ],
            'bonuses' => $this->bonusesSummary($agentId),
        ]);
    }

    /** GET /api/agent/prospects — carte + liste personnelle de prospection de l'agent connecté. */
    public function prospects(Request $req, array $params = []): void
    {
        $this->guardEnabled();

        $agentId = (int) ($_REQUEST['_user']['agent_id'] ?? 0);
        Response::success(AgentProspect::forAgent($agentId));
    }

    /** POST /api/agent/prospects — enregistre un établissement démarché, pas encore inscrit sur la plateforme. */
    public function createProspect(Request $req, array $params = []): void
    {
        $this->guardEnabled();

        $agentId = (int) ($_REQUEST['_user']['agent_id'] ?? 0);

        $name  = trim((string) $req->input('establishment_name', ''));
        $phone = preg_replace('/\D/', '', (string) $req->input('phone', ''));
        if (str_starts_with($phone, '225')) $phone = substr($phone, 3);
        $notes = trim((string) $req->input('notes', ''));

        if ($name === '') Response::error('Le nom de l\'établissement est requis');
        if (mb_strlen($name) > 150) Response::error('Nom trop long (150 caractères max)');
        if (!$this->isValidCiPhone($phone)) {
            Response::error('Numéro invalide : 10 chiffres commençant par 01, 05 ou 07');
        }

        $data = [
            'agent_id'           => $agentId,
            'establishment_name' => $name,
            'phone'              => $phone,
            'notes'              => $notes !== '' ? $notes : null,
            'latitude'           => null,
            'longitude'          => null,
        ];

        [$lat, $lng] = $this->parseLatLng($req);
        $data['latitude']  = $lat;
        $data['longitude'] = $lng;

        $id = AgentProspect::create($data);

        Response::success(AgentProspect::find($id), 'Prospect enregistré', 201);
    }

    /** PUT /api/agent/prospects/{id} — édition des infos et/ou changement de statut. */
    public function updateProspect(Request $req, array $params = []): void
    {
        $this->guardEnabled();

        $agentId  = (int) ($_REQUEST['_user']['agent_id'] ?? 0);
        $id       = (int) ($params['id'] ?? 0);
        $prospect = AgentProspect::findForAgent($id, $agentId);
        if (!$prospect) Response::notFound('Prospect introuvable');

        $input = $req->all();
        $data  = [];

        if (array_key_exists('establishment_name', $input)) {
            $name = trim((string) $req->input('establishment_name', ''));
            if ($name === '') Response::error('Le nom de l\'établissement est requis');
            if (mb_strlen($name) > 150) Response::error('Nom trop long (150 caractères max)');
            $data['establishment_name'] = $name;
        }

        if (array_key_exists('phone', $input)) {
            $phone = preg_replace('/\D/', '', (string) $req->input('phone', ''));
            if (str_starts_with($phone, '225')) $phone = substr($phone, 3);
            if (!$this->isValidCiPhone($phone)) {
                Response::error('Numéro invalide : 10 chiffres commençant par 01, 05 ou 07');
            }
            $data['phone'] = $phone;
        }

        if (array_key_exists('notes', $input)) {
            $notes = trim((string) $req->input('notes', ''));
            $data['notes'] = $notes !== '' ? $notes : null;
        }

        if (array_key_exists('status', $input)) {
            $status = (string) $req->input('status', '');
            if (!in_array($status, AgentProspect::STATUSES, true)) {
                Response::error('Statut invalide');
            }
            $data['status'] = $status;
        }

        if (array_key_exists('latitude', $input) || array_key_exists('longitude', $input)) {
            [$lat, $lng] = $this->parseLatLng($req);
            $data['latitude']  = $lat;
            $data['longitude'] = $lng;
        }

        if (!empty($data)) AgentProspect::update($id, $data);

        Response::success(AgentProspect::find($id), 'Prospect mis à jour');
    }

    /** DELETE /api/agent/prospects/{id} */
    public function deleteProspect(Request $req, array $params = []): void
    {
        $this->guardEnabled();

        $agentId  = (int) ($_REQUEST['_user']['agent_id'] ?? 0);
        $id       = (int) ($params['id'] ?? 0);
        $prospect = AgentProspect::findForAgent($id, $agentId);
        if (!$prospect) Response::notFound('Prospect introuvable');

        AgentProspect::delete($id);

        Response::success(null, 'Prospect supprimé');
    }

    /** Latitude/longitude optionnelles (géolocalisation navigateur) — bornées, sinon NULL plutôt qu'une erreur bloquante. */
    private function parseLatLng(Request $req): array
    {
        $lat = $req->input('latitude');
        $lng = $req->input('longitude');
        if ($lat === null || $lat === '' || $lng === null || $lng === '') return [null, null];

        $lat = (float) $lat;
        $lng = (float) $lng;
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) return [null, null];

        return [$lat, $lng];
    }

    /** Progression vers le prochain lot de 5 pour un plan — forfait effectif (Core\Settings). */
    private function batchProgress(int $agentId, string $plan): array
    {
        return [
            'count'  => AgentReferral::countPending($agentId, $plan),
            'target' => CommissionService::BATCH_SIZE,
            'reward' => CommissionService::rewardForPlan($plan),
        ];
    }

    /** État des 4 primes ponctuelles pour le dashboard agent (montants effectifs, Core\Settings). */
    private function bonusesSummary(int $agentId): array
    {
        $myAwards  = AgentBonusAward::forAgent($agentId);
        $myTypes   = array_column($myAwards, 'type');

        $firstTo5Status = in_array('first_to_5', $myTypes, true)
            ? 'won'
            : (AgentBonusAward::existsForType('first_to_5') ? 'claimed_by_other' : 'open');

        $lastMonth   = (new \DateTime('first day of last month'))->format('Y-m');
        $lastMonthKey = "{$lastMonth}:{$agentId}";
        $wonLastMonth = (bool) array_filter(
            $myAwards,
            fn($a) => $a['type'] === 'monthly_top' && $a['scope_key'] === $lastMonthKey
        );

        // Rang de l'agent connecté sur le mois EN COURS (pas le mois dernier évalué
        // par le job) — donne une indication en temps réel, avant l'attribution
        // effective de la prime au début du mois suivant.
        $thisMonthStart = (new \DateTime('first day of this month'))->format('Y-m-d');
        $nextMonthStart = (new \DateTime('first day of next month'))->format('Y-m-d');
        $ranking = AgentReferral::rankingBetween($thisMonthStart, $nextMonthStart);

        $myRank = null;
        $myCountThisMonth = 0;
        foreach ($ranking as $i => $row) {
            if ((int) $row['agent_id'] === $agentId) {
                $myRank = $i + 1;
                $myCountThisMonth = (int) $row['cnt'];
                break;
            }
        }

        $firstTo5    = CommissionService::firstTo5Config();
        $fastConv    = CommissionService::fastConversionConfig();

        return [
            'first_to_5' => [
                'amount'   => $firstTo5['amount'],
                'target'   => $firstTo5['target'],
                'progress' => AgentReferral::countTotal($agentId),
                'status'   => $firstTo5Status,
            ],
            'first_business' => [
                'amount' => CommissionService::firstBusinessAmount(),
                'status' => in_array('first_business', $myTypes, true) ? 'won' : 'open',
            ],
            'fast_conversion' => [
                'amount' => $fastConv['amount'],
                'days'   => $fastConv['days'],
                'count'  => count(array_filter($myTypes, fn($t) => $t === 'fast_conversion')),
            ],
            'monthly_top' => [
                'amount'         => CommissionService::monthlyTopAmount(),
                'count'          => count(array_filter($myTypes, fn($t) => $t === 'monthly_top')),
                'won_last'       => $wonLastMonth,
                'rank'           => $myRank,
                'rank_referrals' => $myCountThisMonth,
                'total_ranked'   => count($ranking),
            ],
        ];
    }
}
