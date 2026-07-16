<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Infrastructure\Security\Jwt\JwtTokenValidator;
use App\Identity\Infrastructure\Security\Jwt\JwtValidationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Faz a ponte entre o authenticator access_token do Symfony e a camada JWT.
 *
 * Valida o bearer token, guarda os claims para os providers de contexto atual
 * e devolve um UserBadge cujo identificador (o e-mail) é resolvido pelo
 * entity provider "safe_score_users".
 */
final readonly class JwtAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private JwtTokenValidator $validator,
        private RequestAuthenticatedTokenProvider $tokenContext,
    ) {
    }

    public function getUserBadgeFrom(string $accessToken): UserBadge
    {
        try {
            $token = $this->validator->validate($accessToken);
        } catch (JwtValidationException $exception) {
            throw new BadCredentialsException('Access token inválido.', 0, $exception);
        }

        $this->tokenContext->store($token);

        return new UserBadge($token->email);
    }
}
