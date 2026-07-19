<?php

declare(strict_types=1);

namespace App\Tests\Identity\Infrastructure\Security;

use App\Kernel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

final class ProductionFirewallTest extends TestCase
{
    public function testProtectedEndpointRequiresBearerTokenInProduction(): void
    {
        $response = $this->request(Request::create('/api/v1/me', 'GET'));

        self::assertSame(401, $response->getStatusCode());
        self::assertJson((string) $response->getContent());
        self::assertStringContainsString('UNAUTHENTICATED', (string) $response->getContent());
    }

    public function testMalformedBearerTokenIsRejectedInProduction(): void
    {
        $request = Request::create('/api/v1/me', 'GET');
        $request->headers->set('Authorization', 'Bearer malformed-token');
        $response = $this->request($request);

        self::assertSame(401, $response->getStatusCode());
    }

    public function testSpecIdentityAndOrganizationEndpointsRequireAuthentication(): void
    {
        foreach (['/auth/me', '/organizations'] as $path) {
            $response = $this->request(Request::create($path, 'GET'));
            self::assertSame(401, $response->getStatusCode(), $path);
        }
    }

    public function testLocalLoginEndpointDoesNotExist(): void
    {
        $request = Request::create('/auth/login', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');
        $response = $this->request($request);

        self::assertSame(404, $response->getStatusCode());
    }

    private function request(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $kernel = new Kernel('prod', false);
        $response = $kernel->handle($request);
        $kernel->terminate($request, $response);
        $kernel->shutdown();

        return $response;
    }
}
