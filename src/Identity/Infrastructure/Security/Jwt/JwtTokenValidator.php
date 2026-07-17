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
    private const MAX_TOKEN_LENGTH = 16384;

    public function __construct(
        private JwksClient $jwks,
        private string $issuer,
        private string $audience,
        private int $clockSkew = 30,
    ) {
        if ('' === trim($this->issuer) || '' === trim($this->audience)) {
            throw new \InvalidArgumentException('Issuer e audience do JWT devem ser informados.');
        }
        if ($this->clockSkew < 0) {
            throw new \InvalidArgumentException('A tolerância de relógio do JWT não pode ser negativa.');
        }
    }

    public function validate(string $jwt): AuthenticatedToken
    {
        if ('' === $jwt || strlen($jwt) > self::MAX_TOKEN_LENGTH) {
            throw new JwtValidationException('JWT vazio ou acima do tamanho máximo permitido.');
        }

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
        if (isset($header['typ']) && 'JWT' !== $header['typ']) {
            throw new JwtValidationException('O tipo do token deve ser JWT.');
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

        [$issuer, $subject, $email, $organizationId] = $this->validatedClaims($payload);

        return new AuthenticatedToken(
            issuer: $issuer,
            subject: $subject,
            email: $email,
            organizationId: $organizationId,
            roles: $this->extractRoles($payload),
            claims: $payload,
        );
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array{string, string, string, int}
     */
    private function validatedClaims(array $payload): array
    {
        $now = time();

        $expiresAt = $this->integerClaim($payload, 'exp');
        if ($now > $expiresAt + $this->clockSkew) {
            throw new JwtValidationException('O JWT está expirado.');
        }
        if (isset($payload['nbf']) && $now + $this->clockSkew < $this->integerClaim($payload, 'nbf')) {
            throw new JwtValidationException('O JWT ainda não é válido.');
        }

        $issuer = $this->nonEmptyStringClaim($payload, 'iss');
        if ($issuer !== $this->issuer) {
            throw new JwtValidationException('Issuer do JWT inesperado.');
        }

        $audience = $payload['aud'] ?? null;
        if (is_string($audience)) {
            $audiences = [$audience];
        } elseif (is_array($audience) && [] !== $audience && array_is_list($audience) && [] === array_filter($audience, static fn (mixed $value): bool => !is_string($value) || '' === $value)) {
            $audiences = $audience;
        } else {
            throw new JwtValidationException('O claim "aud" deve ser uma string ou lista não vazia de strings.');
        }
        if (!in_array($this->audience, $audiences, true)) {
            throw new JwtValidationException('Audience do JWT não corresponde.');
        }

        $subject = $this->nonEmptyStringClaim($payload, 'sub');
        $email = mb_strtolower(trim($this->nonEmptyStringClaim($payload, 'email')));
        if (false === filter_var($email, \FILTER_VALIDATE_EMAIL)) {
            throw new JwtValidationException('O claim "email" não contém um endereço válido.');
        }
        $organizationId = $this->integerClaim($payload, 'organization_id');
        if ($organizationId < 1) {
            throw new JwtValidationException('O claim "organization_id" deve ser um inteiro positivo.');
        }

        return [$issuer, $subject, $email, $organizationId];
    }

    /** @param array<string, mixed> $payload */
    private function integerClaim(array $payload, string $name): int
    {
        $value = $payload[$name] ?? null;
        if (!is_int($value)) {
            throw new JwtValidationException(sprintf('O claim "%s" deve ser um inteiro.', $name));
        }

        return $value;
    }

    /** @param array<string, mixed> $payload */
    private function nonEmptyStringClaim(array $payload, string $name): string
    {
        $value = $payload[$name] ?? null;
        if (!is_string($value) || '' === trim($value)) {
            throw new JwtValidationException(sprintf('O claim "%s" deve ser uma string não vazia.', $name));
        }

        return trim($value);
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
        if ('' === $input || 1 !== preg_match('/^[A-Za-z0-9_-]+$/D', $input)) {
            throw new JwtValidationException('Codificação base64url inválida no JWT.');
        }

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
