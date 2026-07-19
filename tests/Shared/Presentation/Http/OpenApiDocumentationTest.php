<?php

declare(strict_types=1);

namespace App\Tests\Shared\Presentation\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class OpenApiDocumentationTest extends WebTestCase
{
    public function testOpenApiContainsTechnicalRoutesAndNoFinancialRoutes(): void
    {
        $client = self::createClient();
        $client->request('GET', '/api/docs.jsonopenapi');
        self::assertResponseIsSuccessful();
        $document = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame('Stone Traceability API', $document['info']['title'] ?? null);
        self::assertIsArray($document['paths'] ?? null);
        self::assertSame('bearer', $document['components']['securitySchemes']['bearerAuth']['scheme'] ?? null);
        foreach (['/api/v1/customers', '/api/v1/credit-limits/{id}', '/api/v1/receivables', '/api/v1/customers/{id}/financial-summary'] as $removed) {
            self::assertArrayNotHasKey($removed, $document['paths']);
        }
        self::assertArrayHasKey('/api/v1/imports', $document['paths']);
        foreach (['/auth/me', '/organizations'] as $path) {
            self::assertArrayHasKey($path, $document['paths']);
        }
        self::assertArrayNotHasKey('/auth/login', $document['paths']);
        self::assertArrayNotHasKey('/organizations/{id}/select', $document['paths']);
    }
}
