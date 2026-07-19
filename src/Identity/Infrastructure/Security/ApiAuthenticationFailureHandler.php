<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Security;

use App\Identity\Infrastructure\Security\Jwt\JwksUnavailableException;
use App\Shared\Application\Observability\CorrelationIdProviderInterface;
use App\Shared\Presentation\Http\ApiError;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

final readonly class ApiAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private CorrelationIdProviderInterface $correlationIds,
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): Response
    {
        $correlationId = $this->correlationIds->current();
        $jwksUnavailable = $this->contains($exception, JwksUnavailableException::class);
        $this->logger->warning($jwksUnavailable ? 'External authentication provider unavailable.' : 'External access token rejected.', [
            'correlation_id' => $correlationId,
            'reason' => $jwksUnavailable ? 'jwks_unavailable' : 'invalid_token',
        ]);

        return ApiResponseFactory::error(
            [new ApiError(
                $jwksUnavailable ? 'AUTHENTICATION_PROVIDER_UNAVAILABLE' : 'UNAUTHENTICATED',
                $jwksUnavailable ? 'O serviço de autenticação está temporariamente indisponível.' : 'Autenticação necessária.',
            )],
            $jwksUnavailable ? Response::HTTP_SERVICE_UNAVAILABLE : Response::HTTP_UNAUTHORIZED,
            ['correlation_id' => $correlationId],
        );
    }

    /** @param class-string<\Throwable> $class */
    private function contains(\Throwable $exception, string $class): bool
    {
        do {
            if ($exception instanceof $class) {
                return true;
            }
            $exception = $exception->getPrevious();
        } while (null !== $exception);

        return false;
    }
}
