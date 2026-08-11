<?php

namespace Services;

use Core\Database;
use Cose\Algorithm\Manager;
use Cose\Algorithm\Signature\ECDSA\ES256;
use Cose\Algorithm\Signature\RSA\RS256;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AttestationStatement\NoneAttestationStatementSupport;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredentialParameters;
use Webauthn\PublicKeyCredentialRpEntity;

/**
 * Configuration RP (relying party) et cérémonie WebAuthn partagée entre
 * WebauthnService (gate "app installée", anonyme) et WebauthnLoginService
 * (connexion optionnelle par empreinte, liée à un compte) — extraite pour
 * ne jamais laisser dériver ces deux services (RP id, algos, gestion des
 * défis) l'un par rapport à l'autre. Pas de changement de comportement pour
 * le gate existant lors de cette extraction.
 */
trait WebauthnRelyingPartyTrait
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
}
