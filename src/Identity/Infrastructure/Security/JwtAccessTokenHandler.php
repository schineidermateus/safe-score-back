<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Domain\Repository\UserRepository;
use App\Identity\Infrastructure\Security\Jwt\JwtTokenValidator;
use App\Identity\Infrastructure\Security\Jwt\JwtValidationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

/**
 * Faz a ponte entre o authenticator access_token do Symfony e a camada JWT.
 *
 * Valida o bearer token, guarda os claims para os providers de contexto atual
 * e devolve um UserBadge resolvido pela identidade estável issuer + subject.
 */
final readonly class JwtAccessTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private JwtTokenValidator $validator,
        private RequestAuthenticatedTokenProvider $tokenContext,
        private UserRepository $users,
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

        return new UserBadge($token->subject, function () use ($token) {
            $user = $this->users->findByExternalIdentity($token->issuer, $token->subject);
            if (null === $user) {
                $exception = new UserNotFoundException('Usuário não vinculado à identidade autenticada.');
                $exception->setUserIdentifier($token->subject);

                throw $exception;
            }

            return $user;
        });
    }
}
