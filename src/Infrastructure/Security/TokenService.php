<?php

namespace App\Infrastructure\Security;

use App\Domain\Usuario\Entity\Usuario;
use App\Domain\Usuario\Entity\UsuarioSessao;

class TokenService
{
    public function __construct(
        private string $jwtSecret,
        private string $algorithm = 'HS256'
    ){
    }

    /**
     * Gera um token JWT simples.
     */
    public function createAccessToken(Usuario $user, UsuarioSessao $sessao, int $ttl = 3600): string
    {
        $header = [
            'alg' => $this->algorithm,
            'typ' => 'JWT'
        ];

        $payload = [
            'sub' => $user->getId(),
            'sid' => (string) $sessao->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles() ?? [],
            'iat' => time(),
            'exp' => time() + $ttl
        ];

        return $this->encode($header, $payload);
    }

    /**
     * Valida token e retorna payload decodificado.
     */
    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        [$header64, $payload64, $signature] = $parts;

        $dataToSign = $header64 . '.' . $payload64;
        $expectedSignature = $this->sign($dataToSign);

        if (!hash_equals($expectedSignature, $signature)) {
            return null; // assinatura inválida
        }

        $payload = json_decode(base64_decode($payload64), true);


        if (!$payload || (($payload['exp'] ?? 0) < time())) {
            return null; // expirado
        }

        return $payload;
    }

    private function encode(array $header, array $payload): string
    {
        $header64 = $this->base64url(json_encode($header));
        $payload64 = $this->base64url(json_encode($payload));
        $signature = $this->sign("$header64.$payload64");

        return "$header64.$payload64.$signature";
    }

    private function base64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function sign(string $data): string
    {
        return $this->base64url(
            hash_hmac('sha256', $data, $this->jwtSecret, true)
        );
    }
}
