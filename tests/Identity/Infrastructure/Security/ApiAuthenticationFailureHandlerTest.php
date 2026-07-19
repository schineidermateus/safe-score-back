<?php

declare(strict_types=1);

namespace App\Tests\Identity\Infrastructure\Security;

use App\Identity\Infrastructure\Security\ApiAuthenticationFailureHandler;
use App\Identity\Infrastructure\Security\Jwt\JwksUnavailableException;
use App\Shared\Application\Observability\CorrelationIdProviderInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

final class ApiAuthenticationFailureHandlerTest extends TestCase
{
    public function testInvalidTokenUsesTheStandardUnauthorizedEnvelope(): void
    {
        $response = $this->handler()->onAuthenticationFailure(
            Request::create('/auth/me'),
            new BadCredentialsException('Internal detail that must not be exposed.'),
        );

        self::assertSame(401, $response->getStatusCode());
        self::assertStringContainsString('UNAUTHENTICATED', (string) $response->getContent());
        self::assertStringNotContainsString('Internal detail', (string) $response->getContent());
        self::assertStringContainsString('test-correlation', (string) $response->getContent());
    }

    public function testJwksOutageIsReportedAsAServiceFailureWithoutTechnicalDetails(): void
    {
        $response = $this->handler()->onAuthenticationFailure(
            Request::create('/auth/me'),
            new BadCredentialsException('Invalid token.', 0, new JwksUnavailableException('Provider TLS error.')),
        );

        self::assertSame(503, $response->getStatusCode());
        self::assertStringContainsString('AUTHENTICATION_PROVIDER_UNAVAILABLE', (string) $response->getContent());
        self::assertStringNotContainsString('TLS', (string) $response->getContent());
    }

    private function handler(): ApiAuthenticationFailureHandler
    {
        $correlationIds = new class implements CorrelationIdProviderInterface {
            public function current(): string
            {
                return 'test-correlation';
            }
        };

        return new ApiAuthenticationFailureHandler(new NullLogger(), $correlationIds);
    }
}
