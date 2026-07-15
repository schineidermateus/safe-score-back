<?php

declare(strict_types=1);

namespace App\Tests\Credit\Presentation\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

final class CreditLimitRoutesAndPayloadTest extends WebTestCase
{
    public function testRoutesUseIntegerIdentifiersAndExpectedMethods(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);
        self::assertInstanceOf(RouterInterface::class, $router);
        $routes = $router->getRouteCollection();

        foreach ([
            'credit_limits_history' => ['/api/v1/customers/{customerId}/credit-limits', ['GET'], 'customerId'],
            'credit_limits_active' => ['/api/v1/customers/{customerId}/credit-limits/active', ['GET'], 'customerId'],
            'credit_limits_create' => ['/api/v1/customers/{customerId}/credit-limits', ['POST'], 'customerId'],
            'credit_limits_get' => ['/api/v1/credit-limits/{id}', ['GET'], 'id'],
            'credit_limits_update' => ['/api/v1/credit-limits/{id}', ['PATCH'], 'id'],
            'credit_limits_revoke' => ['/api/v1/credit-limits/{id}/revoke', ['POST'], 'id'],
        ] as $name => [$path, $methods, $idParameter]) {
            $route = $routes->get($name);
            self::assertNotNull($route, $name);
            self::assertSame($path, $route->getPath(), $name);
            self::assertSame($methods, $route->getMethods(), $name);
            self::assertSame('\\d+', $route->getRequirement($idParameter), $name);
        }
    }

    /** @param array<string, mixed> $payload */
    #[DataProvider('tenantPayloadEndpoints')]
    public function testPayloadCannotChooseOrganizationOrCustomer(string $method, string $path, array $payload): void
    {
        $client = self::createClient();
        $client->jsonRequest($method, $path, [...$payload, 'organization_id' => 999, 'customer_id' => 999]);

        self::assertResponseStatusCodeSame(400);
        /** @var array<string, mixed> $response */
        $response = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('BAD_REQUEST', $response['errors'][0]['code'] ?? null);
    }

    /** @return iterable<string, array{string, string, array<string, mixed>}> */
    public static function tenantPayloadEndpoints(): iterable
    {
        $limit = ['amount' => '10.00', 'valid_from' => '2026-01-01', 'valid_until' => null, 'reason' => 'reason'];
        yield 'create' => ['POST', '/api/v1/customers/1/credit-limits', $limit];
        yield 'update' => ['PATCH', '/api/v1/credit-limits/1', $limit];
        yield 'revoke' => ['POST', '/api/v1/credit-limits/1/revoke', ['reason' => 'reason']];
    }
}
