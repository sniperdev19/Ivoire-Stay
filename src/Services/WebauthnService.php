<?php

namespace Services;

use Core\Database;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\RS256;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;

/**
 * Gate PWA "app installée uniquement". La cérémonie WebAuthn cryptographique
 * (navigator.credentials.create(), signature scellée par l'authenticateur de
 * l'appareil — Touch ID, Windows Hello, verrou d'écran Android…) n'a lieu
 * qu'UNE SEULE FOIS, à l'installation : elle mint un `device_token` opaque,
 * renvoyé au client et réutilisé silencieusement à chaque connexion ensuite
 * (pas de nouvelle preuve cryptographique ni d'invite biométrique à chaque
 * tentative — choix produit délibéré pour éviter la friction).
 *
 * Le gain de sécurité par rapport à l'ancien `device_secret` ne vient donc pas
 * d'une vérification répétée, mais du fait que ce jeton ne peut plus être
 * miné par un simple appel HTTP direct à un endpoint d'enregistrement : il
 * faut avoir réellement complété une cérémonie WebAuthn dans un navigateur
 * avec un authenticateur pour l'obtenir. Une fois émis, le `device_token` est
 * un secret porteur comme l'ancien (volable via XSS, rejouable tel quel) —
 * cette limite est assumée, cf. FICHE_SECURITE.md.
 *
 * Anonyme par conception : le "user" WebAuthn n'est pas un compte applicatif
 * (email/mot de passe restent le seul moyen d'identifier la personne), juste
 * un handle aléatoire propre à l'appareil qui a fait la cérémonie d'inscription.
 * Attestation "none" uniquement : on ne cherche pas à valider le modèle exact
 * de l'authenticateur, seulement qu'une vraie cérémonie WebAuthn a eu lieu.
 */
class WebauthnService
{
    private const CHALLENGE_TTL = 300; // secondes

    private static function rpId(): string
    {
        return (string) (parse_url(APP_URL, PHP_URL_HOST) ?: 'localhost');
    }

    private static function rp(): PublicKeyCredentialRpEntity
    {
        return PublicKeyCredentialRpEntity::create('Afristay', self::rpId());
    }

    private static function pubKeyCredParams(): array
    {
        return [
            PublicKeyCredentialParameters::createPk(-7),   // ES256
            PublicKeyCredentialParameters::createPk(-257), // RS256
        ];
    }

    private static function attestationSupportManager(): AttestationStatementSupportManager
    {
        return new AttestationStatementSupportManager([new NoneAttestationStatementSupport()]);
    }

    private static function ceremonyFactory(): CeremonyStepManagerFactory
    {
        $factory = new CeremonyStepManagerFactory();
        $factory->setAlgorithmManager(Manager::create()->add(ES256::create(), RS256::create()));
        $factory->setAttestationStatementSupportManager(self::attestationSupportManager());
        // localhost autorisé en HTTP pour le développement (WAMP) ; tout autre
        // host doit passer par HTTPS (cf. contrainte PWA déjà en place).
        $factory->setSecuredRelyingPartyId(['localhost']);
        return $factory;
    }

    private static function serializer()
    {
        return (new WebauthnSerializerFactory(self::attestationSupportManager()))->create();
    }

    private static function host(): string
    {
        return self::rpId();
    }

    private static function storeChallenge(string $type, string $challengeRaw, ?string $userHandleRaw): string
    {
        $id = self::uuidv4();
        Database::query(
            'INSERT INTO webauthn_challenges (id, type, challenge, user_handle, expires_at) VALUES (?, ?, ?, ?, ?)',
            [
                $id,
                $type,
                base64_encode($challengeRaw),
                $userHandleRaw !== null ? base64_encode($userHandleRaw) : null,
                date('Y-m-d H:i:s', time() + self::CHALLENGE_TTL),
            ]
        );
        // Nettoyage occasionnel des défis expirés (pas de cron dédié).
        if (random_int(1, 30) === 1) {
            Database::query('DELETE FROM webauthn_challenges WHERE expires_at < NOW()');
        }
        return $id;
    }

    private static function consumeChallenge(string $state, string $type): ?array
    {
        $row = Database::query(
            'SELECT * FROM webauthn_challenges WHERE id = ? AND type = ? AND expires_at > NOW()',
            [$state, $type]
        )->fetch();

        if (!$row) return null;

        // À usage unique : supprimé dès lecture, qu'elle réussisse ou non ensuite.
        Database::query('DELETE FROM webauthn_challenges WHERE id = ?', [$state]);
        return $row;
    }

    private static function uuidv4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    // ─── Enregistrement (à l'installation de l'app) ────────────────────────────

    public static function registrationOptions(): array
    {
        $challenge  = random_bytes(32);
        $userHandle = random_bytes(32);

        $state = self::storeChallenge('register', $challenge, $userHandle);

        return [
            'state' => $state,
            'publicKey' => [
                'rp' => ['id' => self::rpId(), 'name' => 'Afristay'],
                'user' => [
                    'id'          => Base64UrlSafe::encodeUnpadded($userHandle),
                    'name'        => 'device-' . substr(bin2hex($userHandle), 0, 8),
                    'displayName' => 'Appareil Afristay',
                ],
                'challenge' => Base64UrlSafe::encodeUnpadded($challenge),
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => -7],
                    ['type' => 'public-key', 'alg' => -257],
                ],
                'authenticatorSelection' => [
                    'residentKey'      => 'required',
                    'userVerification' => 'preferred',
                ],
                'attestation' => 'none',
                'timeout' => 60000,
            ],
        ];
    }

    /**
     * Vérifie la cérémonie d'enregistrement et retourne le device_token opaque
     * à conserver côté client (localStorage) pour les connexions suivantes.
     *
     * @throws AuthenticatorResponseVerificationException
     */
    public static function verifyRegistration(string $state, array $credentialJson): string
    {
        $row = self::consumeChallenge($state, 'register');
        if (!$row) {
            throw AuthenticatorResponseVerificationException::create('Défi invalide ou expiré.');
        }

        $challengeRaw  = base64_decode($row['challenge']);
        $userHandleRaw = base64_decode($row['user_handle']);

        $options = PublicKeyCredentialCreationOptions::create(
            self::rp(),
            PublicKeyCredentialUserEntity::create('device', $userHandleRaw, 'Appareil Afristay'),
            $challengeRaw,
            self::pubKeyCredParams(),
            AuthenticatorSelectionCriteria::create(
                null,
                AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_PREFERRED,
                AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED
            ),
            PublicKeyCredentialCreationOptions::ATTESTATION_CONVEYANCE_PREFERENCE_NONE
        );

        $serializer = self::serializer();
        /** @var PublicKeyCredential $credential */
        $credential = $serializer->deserialize(json_encode($credentialJson), PublicKeyCredential::class, 'json');

        if (!$credential->response instanceof AuthenticatorAttestationResponse) {
            throw AuthenticatorResponseVerificationException::create('Réponse d\'enregistrement invalide.');
        }

        $validator = AuthenticatorAttestationResponseValidator::create(self::ceremonyFactory()->creationCeremony());
        $record    = $validator->check($credential->response, $options, self::host());

        $deviceToken = bin2hex(random_bytes(32));

        Database::query(
            'INSERT INTO webauthn_credentials
                (credential_id, credential_type, transports, attestation_type, aaguid, public_key, user_handle, device_token_hash, counter, backup_eligible, backup_status, uv_initialized)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                base64_encode($record->publicKeyCredentialId),
                $record->type,
                json_encode($record->transports),
                $record->attestationType,
                $record->aaguid->toRfc4122(),
                base64_encode($record->credentialPublicKey),
                base64_encode($record->userHandle),
                hash('sha256', $deviceToken),
                $record->counter,
                $record->backupEligible === null ? null : (int) $record->backupEligible,
                $record->backupStatus === null ? null : (int) $record->backupStatus,
                $record->uvInitialized === null ? null : (int) $record->uvInitialized,
            ]
        );

        return $deviceToken;
    }

    // ─── Connexion (gate avant vérification email/mot de passe) ────────────────
    // Pas de nouvelle cérémonie WebAuthn ici : simple vérification du jeton
    // opaque miné une fois pour toutes par verifyRegistration() ci-dessus.

    public static function isDeviceTokenValid(string $token): bool
    {
        if ($token === '') return false;

        $row = Database::query(
            'SELECT id FROM webauthn_credentials WHERE device_token_hash = ? AND revoked_at IS NULL',
            [hash('sha256', $token)]
        )->fetch();

        if (!$row) return false;

        Database::query('UPDATE webauthn_credentials SET last_used_at = NOW() WHERE id = ?', [$row['id']]);
        return true;
    }
}
