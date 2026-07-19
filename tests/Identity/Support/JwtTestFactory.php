<?php

declare(strict_types=1);

namespace App\Tests\Identity\Support;

final class JwtTestFactory
{
    private \OpenSSLAsymmetricKey $privateKey;

    public function __construct()
    {
        $configuration = [
            'private_key_bits' => 2048,
            'private_key_type' => \OPENSSL_KEYTYPE_RSA,
        ];
        $portableConfig = dirname(__DIR__, 3).'/.tools/php/extras/ssl/openssl.cnf';
        if (is_file($portableConfig)) {
            $configuration['config'] = $portableConfig;
        }
        $key = openssl_pkey_new($configuration);
        if (false === $key) {
            throw new \RuntimeException('Não foi possível gerar a chave RSA de teste.');
        }
        $this->privateKey = $key;
    }

    /** @return array<string, mixed> */
    public function claims(): array
    {
        return [
            'iss' => 'https://auth.stone.local',
            'sub' => 'user:123',
            'email' => 'user@example.com',
            'organization_id' => 42,
            'aud' => 'stone-traceability-api',
            'iat' => time() - 10,
            'nbf' => time() - 10,
            'exp' => time() + 300,
            'roles' => ['ignored-token-role'],
        ];
    }

    /**
     * @param array<string, mixed> $claims
     * @param array<string, mixed> $header
     */
    public function token(array $claims, array $header = []): string
    {
        $header += ['alg' => 'RS256', 'typ' => 'at+jwt', 'kid' => 'key-1'];
        $encodedHeader = self::encode(json_encode($header, \JSON_THROW_ON_ERROR));
        $encodedPayload = self::encode(json_encode($claims, \JSON_THROW_ON_ERROR));
        $signature = '';
        if (!openssl_sign($encodedHeader.'.'.$encodedPayload, $signature, $this->privateKey, \OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('Não foi possível assinar o JWT de teste.');
        }

        return $encodedHeader.'.'.$encodedPayload.'.'.self::encode($signature);
    }

    /** @return array<string, mixed> */
    public function jwk(string $kid = 'key-1'): array
    {
        $details = openssl_pkey_get_details($this->privateKey);
        if (false === $details || !isset($details['rsa']['n'], $details['rsa']['e'])) {
            throw new \RuntimeException('Não foi possível obter os detalhes da chave RSA de teste.');
        }

        return [
            'kty' => 'RSA',
            'kid' => $kid,
            'use' => 'sig',
            'alg' => 'RS256',
            'key_ops' => ['verify'],
            'n' => self::encode($details['rsa']['n']),
            'e' => self::encode($details['rsa']['e']),
        ];
    }

    public function exportPrivateKey(): string
    {
        $privateKey = '';
        $configuration = [];
        $portableConfig = dirname(__DIR__, 3).'/.tools/php/extras/ssl/openssl.cnf';
        if (is_file($portableConfig)) {
            $configuration['config'] = $portableConfig;
        }
        if (!openssl_pkey_export($this->privateKey, $privateKey, null, $configuration)) {
            throw new \RuntimeException('Não foi possível exportar a chave RSA de teste.');
        }

        return $privateKey;
    }

    private static function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
