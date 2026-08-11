<?php

namespace Services;

use Core\Database;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Symfony\Component\Uid\Uuid;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\CredentialRecord;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\TrustPath\EmptyTrustPath;

/**
 * Connexion optionnelle par empreinte digitale / Face ID / Windows Hello, en
 * complément du mot de passe — JAMAIS un remplacement ni une obligation.
 * Chaque credential est lié à un vrai compte (`user_id`), contrairement à
 * WebauthnService (gate "app installée", anonyme par conception, ne pas
 * confondre les deux) — voir memoire.md pour le contexte produit complet.
 *
 * Connexion "discoverable" (sans email préalable) : l'utilisateur choisit son
 * empreinte directement, le navigateur renvoie l'identifiant de credential
 * qui permet de retrouver le compte, sans lui redemander qui il est.
 */
class WebauthnLoginService
{
    use WebauthnRelyingPartyTrait;

    // ─── Enrôlement (depuis Paramètres, utilisateur déjà connecté) ─────────────

    public static function registrationOptions(int $userId, string $email, string $name): array
    {
        $challenge  = random_bytes(32);
        $userHandle = random_bytes(32);

        $state = self::storeChallenge('login_register', $challenge, $userHandle);

        return [
            'state' => $state,
            'publicKey' => [
                'rp' => ['id' => self::rpId(), 'name' => 'Afristay'],
                'user' => [
                    'id'          => Base64UrlSafe::encodeUnpadded($userHandle),
                    'name'        => $email,
                    'displayName' => $name,
                ],
                'challenge' => Base64UrlSafe::encodeUnpadded($challenge),
                'pubKeyCredParams' => [
                    ['type' => 'public-key', 'alg' => -7],
                    ['type' => 'public-key', 'alg' => -257],
                ],
                'authenticatorSelection' => [
                    // 'platform' = biométrie/PIN intégré à l'appareil, pas de clé
                    // de sécurité USB externe — cohérent avec "empreinte digitale".
                    'authenticatorAttachment' => 'platform',
                    'residentKey'             => 'preferred',
                    'userVerification'        => 'required',
                ],
                'attestation' => 'none',
                'timeout' => 60000,
                // Empêche de ré-enrôler deux fois le même authentificateur pour ce compte.
                'excludeCredentials' => array_map(
                    fn(array $c) => ['type' => 'public-key', 'id' => $c['credential_id']],
                    self::rawCredentialsForUser($userId)
                ),
            ],
        ];
    }

    private static function rawCredentialsForUser(int $userId): array
    {
        return Database::query(
            'SELECT credential_id FROM webauthn_login_credentials WHERE user_id = ? AND revoked_at IS NULL',
            [$userId]
        )->fetchAll();
    }

    /**
     * Vérifie la cérémonie d'enregistrement et enregistre le credential pour ce compte.
     *
     * @throws AuthenticatorResponseVerificationException
     */
    public static function verifyRegistration(string $state, array $credentialJson, int $userId, string $deviceLabel): int
    {
        $row = self::consumeChallenge($state, 'login_register');
        if (!$row) {
            throw AuthenticatorResponseVerificationException::create('Défi invalide ou expiré.');
        }

        $challengeRaw  = base64_decode($row['challenge']);
        $userHandleRaw = base64_decode($row['user_handle']);

        $options = PublicKeyCredentialCreationOptions::create(
            self::rp(),
            PublicKeyCredentialUserEntity::create('user-' . $userId, $userHandleRaw, 'Afristay'),
            $challengeRaw,
            self::pubKeyCredParams(),
            AuthenticatorSelectionCriteria::create(
                'platform',
                AuthenticatorSelectionCriteria::USER_VERIFICATION_REQUIREMENT_REQUIRED,
                AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_PREFERRED
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

        Database::query(
            'INSERT INTO webauthn_login_credentials
                (user_id, credential_id, credential_type, transports, aaguid, public_key, user_handle, counter, device_label, backup_eligible, backup_status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $userId,
                base64_encode($record->publicKeyCredentialId),
                $record->type,
                json_encode($record->transports),
                $record->aaguid->toRfc4122(),
                base64_encode($record->credentialPublicKey),
                base64_encode($record->userHandle),
                $record->counter,
                $deviceLabel,
                $record->backupEligible === null ? null : (int) $record->backupEligible,
                $record->backupStatus === null ? null : (int) $record->backupStatus,
            ]
        );

        return (int) Database::lastInsertId();
    }

    public static function listForUser(int $userId): array
    {
        return Database::query(
            'SELECT id, device_label, created_at, last_used_at
             FROM webauthn_login_credentials
             WHERE user_id = ? AND revoked_at IS NULL
             ORDER BY created_at DESC',
            [$userId]
        )->fetchAll();
    }

    /** Révoque un credential — vérifie l'appartenance avant, comme les autres "appareils" (user_sessions). */
    public static function revoke(int $id, int $userId): bool
    {
        $stmt = Database::query(
            'UPDATE webauthn_login_credentials SET revoked_at = NOW() WHERE id = ? AND user_id = ? AND revoked_at IS NULL',
            [$id, $userId]
        );
        return $stmt->rowCount() > 0;
    }

    // ─── Connexion (discoverable, pas besoin de connaître l'email au préalable) ─

    public static function loginOptions(): array
    {
        $challenge = random_bytes(32);
        $state     = self::storeChallenge('login', $challenge, null);

        return [
            'state' => $state,
            'publicKey' => [
                'rpId'             => self::rpId(),
                'challenge'        => Base64UrlSafe::encodeUnpadded($challenge),
                'allowCredentials' => [], // discoverable : le navigateur propose les passkeys disponibles pour ce site
                'userVerification' => 'required',
                'timeout'          => 60000,
            ],
        ];
    }

    /** Retourne l'id utilisateur si la cérémonie est valide, sinon null (jamais d'exception laissée remonter — appelant public, sans auth). */
    public static function verifyLogin(string $state, array $credentialJson): ?int
    {
        $row = self::consumeChallenge($state, 'login');
        if (!$row) return null;

        $challengeRaw = base64_decode($row['challenge']);

        try {
            $serializer = self::serializer();
            /** @var PublicKeyCredential $credential */
            $credential = $serializer->deserialize(json_encode($credentialJson), PublicKeyCredential::class, 'json');

            if (!$credential->response instanceof AuthenticatorAssertionResponse) {
                return null;
            }

            $stored = Database::query(
                'SELECT * FROM webauthn_login_credentials WHERE credential_id = ? AND revoked_at IS NULL',
                [base64_encode($credential->rawId)]
            )->fetch();
            if (!$stored) return null;

            $credentialRecord = CredentialRecord::create(
                base64_decode($stored['credential_id']),
                $stored['credential_type'],
                json_decode($stored['transports'] ?? '[]', true) ?: [],
                'none',
                new EmptyTrustPath(),
                Uuid::fromString($stored['aaguid']),
                base64_decode($stored['public_key']),
                base64_decode($stored['user_handle']),
                (int) $stored['counter'],
                null,
                $stored['backup_eligible'] === null ? null : (bool) $stored['backup_eligible'],
                $stored['backup_status'] === null ? null : (bool) $stored['backup_status'],
                null
            );

            $options = PublicKeyCredentialRequestOptions::create(
                $challengeRaw,
                self::rpId(),
                [],
                PublicKeyCredentialRequestOptions::USER_VERIFICATION_REQUIREMENT_REQUIRED
            );

            $validator = AuthenticatorAssertionResponseValidator::create(self::ceremonyFactory()->requestCeremony());
            $updated   = $validator->check(
                $credentialRecord,
                $credential->response,
                $options,
                self::host(),
                base64_decode($stored['user_handle'])
            );

            Database::query(
                'UPDATE webauthn_login_credentials SET counter = ?, last_used_at = NOW() WHERE id = ?',
                [$updated->counter, $stored['id']]
            );

            return (int) $stored['user_id'];
        } catch (\Throwable $e) {
            error_log('[WebauthnLoginService] verifyLogin échec — ' . get_class($e) . ' : ' . $e->getMessage());
            return null;
        }
    }
}
