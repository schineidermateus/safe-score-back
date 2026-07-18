<?php

declare(strict_types=1);

namespace App\Tests\Receivables\Presentation\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

final class ReceivableRoutesAndPayloadTest extends WebTestCase
{
    public function testRoutesUseIntegerIdentifiersAndExpectedMethods(): void
    {
        self::bootKernel();
        $router = self::getContainer()->get(RouterInterface::class);
        self::assertInstanceOf(RouterInterface::class, $router);
        $routes = $router->getRouteCollection();

        foreach ([
            'receivables_list' => ['/api/v1/receivables', ['GET'], null],
            'receivables_create' => ['/api/v1/receivables', ['POST'], null],
            'receivables_get' => ['/api/v1/receivables/{id}', ['GET'], 'id'],
            'receivables_update' => ['/api/v1/receivables/{id}', ['PATCH'], 'id'],
            'receivables_payment_register' => ['/api/v1/receivables/{id}/payments', ['POST'], 'id'],
            'receivables_cancel' => ['/api/v1/receivables/{id}/cancel', ['POST'], 'id'],
        ] as $name => [$path, $methods, $idParameter]) {
            $route = $routes->get($name);
            self::assertNotNull($route, $name);
            self::assertSame($path, $route->getPath(), $name);
            self::assertSame($methods, $route->getMethods(), $name);
            if (null !== $idParameter) {
                self::assertSame('\\d+', $route->getRequirement($idParameter), $name);
            }
        }
    }

    /** @param array<string, mixed> $payload */
    #[DataProvider('protectedPayloads')]
    public function testPayloadCannotChooseTenantSourceOrImmutableRelations(string $method, string $path, array $payload): void
    {
        $client = self::createClient();
        $client->jsonRequest($method, $path, $payload);

        self::assertResponseStatusCodeSame(400);
        /** @var array<string, mixed> $response */
        $response = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('BAD_REQUEST', $response['errors'][0]['code'] ?? null);
    }

    /** @return iterable<string, array{string, string, array<string, mixed>}> */
    public static function protectedPayloads(): iterable
    {
        $create = ['customer_id' => 1, 'document_number' => 'NF-1', 'issue_date' => '2026-01-01', 'due_date' => '2026-02-01', 'original_amount' => '10.00'];
        $update = ['document_number' => 'NF-1', 'issue_date' => '2026-01-01', 'due_date' => '2026-02-01', 'original_amount' => '10.00'];
        yield 'create organization' => ['POST', '/api/v1/receivables', [...$create, 'organization_id' => 999]];
        yield 'create source' => ['POST', '/api/v1/receivables', [...$create, 'source' => 'ERP']];
        yield 'update organization' => ['PATCH', '/api/v1/receivables/1', [...$update, 'organization_id' => 999]];
        yield 'update customer' => ['PATCH', '/api/v1/receivables/1', [...$update, 'customer_id' => 999]];
        yield 'payment organization' => ['POST', '/api/v1/receivables/1/payments', ['amount' => '1.00', 'payment_date' => '2026-01-01', 'organization_id' => 999]];
        yield 'cancel organization' => ['POST', '/api/v1/receivables/1/cancel', ['reason' => 'Duplicado', 'organization_id' => 999]];
    }
}
