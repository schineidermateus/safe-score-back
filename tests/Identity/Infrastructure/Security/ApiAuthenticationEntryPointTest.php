<?php

declare(strict_types=1);

namespace App\Tests\Identity\Infrastructure\Security;

use App\Identity\Infrastructure\Security\ApiAuthenticationEntryPoint;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ApiAuthenticationEntryPointTest extends TestCase
{
    public function testItReturnsTheStandardUnauthenticatedResponse(): void
    {
        $response = (new ApiAuthenticationEntryPoint())->start(Request::create('/api/v1/me'));

        self::assertSame(401, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString(
            <<<'JSON'
            {
                "data": null,
                "meta": {},
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
