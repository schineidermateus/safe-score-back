<?php

declare(strict_types=1);

namespace App\Tests\Customers\Presentation\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

final class CustomerRoutesAndPayloadTest extends WebTestCase
{
    public function testCustomerRoutesUseIntegerIdentifiers(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);
        self::assertInstanceOf(RouterInterface::class, $router);
        $routes = $router->getRouteCollection();

        foreach ([
            'customers_list' => ['/api/v1/customers', ['GET'], null],
            'customers_create' => ['/api/v1/customers', ['POST'], null],
            'customers_get' => ['/api/v1/customers/{id}', ['GET'], '\\d+'],
            'customers_update' => ['/api/v1/customers/{id}', ['PATCH'], '\\d+'],
            'customers_delete' => ['/api/v1/customers/{id}', ['DELETE'], '\\d+'],
        ] as $name => [$path, $methods, $idRequirement]) {
            $route = $routes->get($name);
            self::assertNotNull($route, $name);
            self::assertSame($path, $route->getPath(), $name);
            self::assertSame($methods, $route->getMethods(), $name);
            self::assertSame($idRequirement, $route->getRequirement('id'), $name);
        }
    }

    #[DataProvider('writeEndpoints')]
    public function testOrganizationIdFromPayloadCannotSelectTheCustomerTenant(string $method, string $path): void
    {
        $client = self::createClient();
        $client->jsonRequest($method, $path, [
            'legal_name' => 'Cliente',
            'organization_id' => 999,
        ]);

        self::assertResponseStatusCodeSame(400);
        /** @var array<string, mixed> $response */
        $response = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('BAD_REQUEST', $response['errors'][0]['code'] ?? null);
    }

    /** @return iterable<string, array{string, string}> */
    public static function writeEndpoints(): iterable
    {
        yield 'create' => ['POST', '/api/v1/customers'];
        yield 'update' => ['PATCH', '/api/v1/customers/123'];
    }
}
