<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security\Jwt;

/**
 * Converte um JWK RSA (módulo/expoente) em uma chave pública PEM que a
 * ext-openssl consegue consumir, montando manualmente o DER SubjectPublicKeyInfo.
 *
 * Isso mantém o projeto livre de dependência JWT de terceiros: a verificação
 * de assinatura só precisa da chave pública, nunca do material de assinatura.
 */
final class JwkToPem
{
    /**
     * @param array<string, mixed> $jwk
     */
    public static function convert(array $jwk): string
    {
        $modulus = self::decode(isset($jwk['n']) && is_string($jwk['n']) ? $jwk['n'] : null);
        $exponent = self::decode(isset($jwk['e']) && is_string($jwk['e']) ? $jwk['e'] : null);

        if ('' === $modulus || '' === $exponent) {
            throw new JwtValidationException('JWK RSA incompleto: módulo ou expoente ausente.');
        }

        $rsaPublicKey = self::sequence(self::integer($modulus).self::integer($exponent));

        // AlgorithmIdentifier ::= SEQUENCE { rsaEncryption (1.2.840.113549.1.1.1), NULL }
        $algorithm = "\x30\x0d\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01\x05\x00";
        $subjectPublicKeyInfo = self::sequence($algorithm.self::bitString($rsaPublicKey));

        return "-----BEGIN PUBLIC KEY-----\n"
            .chunk_split(base64_encode($subjectPublicKeyInfo), 64, "\n")
            ."-----END PUBLIC KEY-----\n";
    }

    private static function decode(?string $value): string
    {
        if (null === $value) {
            return '';
        }

        $normalized = strtr($value, '-_', '+/');
        $remainder = strlen($normalized) % 4;
        if (0 !== $remainder) {
            $normalized .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($normalized, true);

        return false === $decoded ? '' : $decoded;
    }

    private static function integer(string $bytes): string
    {
        if ('' === $bytes) {
            $bytes = "\x00";
        }

        // Um bit alto no início seria lido como número negativo; prefixa 0x00 para mantê-lo positivo.
        if (0 !== (ord($bytes[0]) & 0x80)) {
            $bytes = "\x00".$bytes;
        }

        return "\x02".self::length(strlen($bytes)).$bytes;
    }

    private static function sequence(string $contents): string
    {
        return "\x30".self::length(strlen($contents)).$contents;
    }

    private static function bitString(string $contents): string
    {
        // 0x00 inicial = quantidade de bits não usados no último byte (nenhum aqui).
        $contents = "\x00".$contents;

        return "\x03".self::length(strlen($contents)).$contents;
    }

    private static function length(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }

        $bytes = '';
        while ($length > 0) {
            $bytes = chr($length & 0xff).$bytes;
            $length >>= 8;
        }

        return chr(0x80 | strlen($bytes)).$bytes;
    }
}
