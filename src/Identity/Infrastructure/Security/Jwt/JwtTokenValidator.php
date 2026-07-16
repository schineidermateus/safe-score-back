<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security\Jwt;

use App\Identity\Application\Context\AuthenticatedToken;

/**
 * Valida um JWS compacto (JWT) emitido pelo serviço de autenticação e o converte
 * em um {@see AuthenticatedToken}. Apenas RS256 é aceito; a chave de assinatura é
 * resolvida no endpoint JWKS pelo header "kid" do token.
 */
final readonly class JwtTokenValidator
{
    public function __construct(
        private JwksClient $jwks,
        private string $issuer,
        private string $audience,
        private int $clockSkew = 30,
    ) {
    }

    public function validate(string $jwt): AuthenticatedToken
    {
        $segments = explode('.', $jwt);
        if (3 !== count($segments)) {
            throw new JwtValidationException('JWT malformado: eram esperados três segmentos.');
        }

        [$encodedHeader, $encodedPayload, $encodedSignature] = $segments;

        $header = $this->decodeSegment($encodedHeader);
        $payload = $this->decodeSegment($encodedPayload);

        if ('RS256' !== ($header['alg'] ?? null)) {
            throw new JwtValidationException('Algoritmo de assinatura não suportado; RS256 é obrigatório.');
        }

        $kid = $header['kid'] ?? null;
        if (!is_string($kid) || '' === $kid) {
            throw new JwtValidationException('O header do JWT não contém um key id (kid) válido.');
        }

        $publicKey = $this->jwks->publicKeyForKid($kid);
        $signature = $this->base64UrlDecode($encodedSignature);

        $verified = openssl_verify(
            $encodedHeader.'.'.$encodedPayload,
            $signature,
            $publicKey,
            \OPENSSL_ALGO_SHA256,
        );

        if (1 !== $verified) {
            throw new JwtValidationException('Falha na verificação da assinatura do JWT.');
        }

        $this->assertClaims($payload);

        $email = $payload['email'] ?? null;
        if (!is_string($email) || '' === $email) {
            throw new JwtValidationException('O JWT não contém o claim "email".');
        }

        return new AuthenticatedToken(
            email: $email,
            organizationId: isset($payload['organization_id']) ? (int) $payload['organization_id'] : null,
            subject: isset($payload['sub']) ? (string) $payload['sub'] : null,
            roles: $this->extractRoles($payload),
            claims: $payload,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function assertClaims(array $payload): void
    {
        $now = time();

        if (!isset($payload['exp'])) {
            throw new JwtValidationException('O JWT não contém o claim "exp".');
        }
        if ($now > (int) $payload['exp'] + $this->clockSkew) {
            throw new JwtValidationException('O JWT está expirado.');
        }
        if (isset($payload['nbf']) && $now + $this->clockSkew < (int) $payload['nbf']) {
            throw new JwtValidationException('O JWT ainda não é válido.');
        }
        if (($payload['iss'] ?? null) !== $this->issuer) {
            throw new JwtValidationException('Issuer do JWT inesperado.');
        }

        $audience = $payload['aud'] ?? null;
        $audiences = is_array($audience) ? $audience : [$audience];
        if (!in_array($this->audience, $audiences, true)) {
            throw new JwtValidationException('Audience do JWT não corresponde.');
        }
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return list<string>
     */
    private function extractRoles(array $payload): array
    {
        $roles = $payload['roles'] ?? [];
        if (!is_array($roles)) {
            return [];
        }

        return array_values(array_filter($roles, 'is_string'));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeSegment(string $segment): array
    {
        try {
            $decoded = json_decode($this->base64UrlDecode($segment), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new JwtValidationException('Segmento do JWT inválido: não é um JSON válido.', 0, $exception);
        }

        if (!is_array($decoded)) {
            throw new JwtValidationException('Segmento do JWT inválido: era esperado um objeto JSON.');
        }

        return $decoded;
    }

    private function base64UrlDecode(string $input): string
    {
        $normalized = strtr($input, '-_', '+/');
        $remainder = strlen($normalized) % 4;
        if (0 !== $remainder) {
            $normalized .= str_repeat('=', 4 - $remainder);
        }

        $decoded = base64_decode($normalized, true);
        if (false === $decoded) {
            throw new JwtValidationException('Codificação base64url inválida no JWT.');
        }

        return $decoded;
    }
}
