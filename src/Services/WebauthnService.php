<?php

namespace Services;

use Core\Database;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\Exception\AuthenticatorResponseVerificationException;
use Webauthn\PublicKeyCredential;
use Webauthn\PublicKeyCredentialCreationOptions;
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
    use WebauthnRelyingPartyTrait;

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
                    // 'discouraged' (et non 'required') : ce gate ne fait jamais de
                    // sélection humaine dans un menu de clés d'accès — il ne s'appuie
                    // que sur le device_token opaque renvoyé une fois et caché en
                    // localStorage. Une clé "découvrable" n'apportait donc rien ici,
                    // sauf polluer le sélecteur natif (Windows Hello, Touch ID...) que
                    // voit l'utilisateur pour la connexion RÉELLE par empreinte
                    // (Services\WebauthnLoginService, même RP id donc même sélecteur) —
                    // constaté en conditions réelles le 2026-08-11.
                    'residentKey'      => 'discouraged',
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
                AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_DISCOURAGED
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
