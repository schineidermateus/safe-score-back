<?php

declare(strict_types=1);

namespace App\Tests\Identity\Infrastructure\Security;

use App\Identity\Infrastructure\Security\ApiAuthenticationEntryPoint;
use App\Shared\Application\Observability\CorrelationIdProviderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiAuthenticationEntryPointTest extends TestCase
{
    public function testItReturnsTheStandardUnauthenticatedResponse(): void
    {
        $correlationIds = new class implements CorrelationIdProviderInterface {
            public function current(): string
            {
                return 'test-correlation';
            }
        };
        $response = (new ApiAuthenticationEntryPoint($correlationIds))->start(Request::create('/api/v1/me'));

        self::assertSame(401, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            <<<'JSON'
            {
                "data": null,
                "meta": {"correlation_id": "test-correlation"},
                "errors": [
                    {
                        "code": "UNAUTHENTICATED",
                        "message": "Autenticação necessária."
                    }
                ]
            }
            JSON,
            (string) $response->getContent(),
        );
    }
}
