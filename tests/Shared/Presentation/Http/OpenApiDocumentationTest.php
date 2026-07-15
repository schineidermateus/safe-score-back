<?php

declare(strict_types=1);

namespace App\Tests\Shared\Presentation\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OpenApiDocumentationTest extends WebTestCase
{
    public function testOpenApiDocumentIsAvailable(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/docs.jsonopenapi');

        self::assertResponseIsSuccessful();

        /** @var array<string, mixed> $document */
        $document = json_decode(
            (string) $client->getResponse()->getContent(),
            true,
            flags: \JSON_THROW_ON_ERROR,
        );

        self::assertSame('SafeScore API', $document['info']['title'] ?? null);
        self::assertSame('1.0.0', $document['info']['version'] ?? null);

        $collection = $document['paths']['/api/v1/customers'] ?? null;
        $item = $document['paths']['/api/v1/customers/{id}'] ?? null;
        self::assertIsArray($collection);
        self::assertIsArray($item);
        self::assertArrayHasKey('get', $collection);
        self::assertArrayHasKey('post', $collection);
        self::assertArrayHasKey('get', $item);
        self::assertArrayHasKey('patch', $item);
        self::assertArrayHasKey('delete', $item);
        self::assertSame('integer', $item['parameters'][0]['schema']['type'] ?? null);

        $requestProperties = $collection['post']['requestBody']['content']['application/json']['schema']['properties'] ?? [];
        $updateProperties = $item['patch']['requestBody']['content']['application/json']['schema']['properties'] ?? [];
        self::assertIsArray($requestProperties);
        self::assertIsArray($updateProperties);
        self::assertArrayNotHasKey('organization_id', $requestProperties);
        self::assertArrayNotHasKey('organization_id', $updateProperties);
        self::assertFalse($collection['post']['requestBody']['content']['application/json']['schema']['additionalProperties'] ?? true);
        self::assertFalse($item['patch']['requestBody']['content']['application/json']['schema']['additionalProperties'] ?? true);
        self::assertSame(
            'integer',
            $collection['post']['responses']['201']['content']['application/json']['schema']['properties']['data']['properties']['id']['type'] ?? null,
        );
        self::assertStringNotContainsString('uuid', strtolower(json_encode([$collection, $item], \JSON_THROW_ON_ERROR)));
    }
}
