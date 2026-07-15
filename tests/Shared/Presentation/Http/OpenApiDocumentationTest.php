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

        $creditCollection = $document['paths']['/api/v1/customers/{customerId}/credit-limits'] ?? null;
        $activeCredit = $document['paths']['/api/v1/customers/{customerId}/credit-limits/active'] ?? null;
        $creditItem = $document['paths']['/api/v1/credit-limits/{id}'] ?? null;
        $creditRevoke = $document['paths']['/api/v1/credit-limits/{id}/revoke'] ?? null;
        self::assertIsArray($creditCollection);
        self::assertIsArray($activeCredit);
        self::assertIsArray($creditItem);
        self::assertIsArray($creditRevoke);
        self::assertArrayHasKey('get', $creditCollection);
        self::assertArrayHasKey('post', $creditCollection);
        self::assertArrayHasKey('get', $activeCredit);
        self::assertArrayHasKey('get', $creditItem);
        self::assertArrayHasKey('patch', $creditItem);
        self::assertArrayHasKey('post', $creditRevoke);

        $creditCreateSchema = $creditCollection['post']['requestBody']['content']['application/json']['schema'] ?? [];
        $creditOutput = $creditCollection['post']['responses']['201']['content']['application/json']['schema']['properties']['data']['properties'] ?? [];
        self::assertIsArray($creditCreateSchema);
        self::assertIsArray($creditOutput);
        self::assertFalse($creditCreateSchema['additionalProperties'] ?? true);
        self::assertArrayNotHasKey('organization_id', $creditCreateSchema['properties'] ?? []);
        self::assertArrayNotHasKey('customer_id', $creditCreateSchema['properties'] ?? []);
        self::assertSame('string', $creditCreateSchema['properties']['amount']['type'] ?? null);
        self::assertSame('string', $creditOutput['amount']['type'] ?? null);
        self::assertArrayNotHasKey('organization_id', $creditOutput);

        $receivables = $document['paths']['/api/v1/receivables'] ?? null;
        $receivable = $document['paths']['/api/v1/receivables/{id}'] ?? null;
        $payments = $document['paths']['/api/v1/receivables/{id}/payments'] ?? null;
        $cancel = $document['paths']['/api/v1/receivables/{id}/cancel'] ?? null;
        self::assertIsArray($receivables);
        self::assertIsArray($receivable);
        self::assertIsArray($payments);
        self::assertIsArray($cancel);
        self::assertArrayHasKey('get', $receivables);
        self::assertArrayHasKey('post', $receivables);
        self::assertArrayHasKey('get', $receivable);
        self::assertArrayHasKey('patch', $receivable);
        self::assertArrayHasKey('post', $payments);
        self::assertArrayHasKey('post', $cancel);
        $receivableInput = $receivables['post']['requestBody']['content']['application/json']['schema'] ?? [];
        self::assertFalse($receivableInput['additionalProperties'] ?? true);
        self::assertArrayNotHasKey('organization_id', $receivableInput['properties'] ?? []);
        self::assertSame('string', $receivableInput['properties']['original_amount']['type'] ?? null);
        $paymentAmount = $payments['post']['requestBody']['content']['application/json']['schema']['properties']['amount'] ?? [];
        self::assertSame('string', $paymentAmount['type'] ?? null);
        self::assertSame('^(?:(?:[1-9]\\d{0,16})(?:\\.\\d{1,2})?|0\\.(?:0[1-9]|[1-9]\\d))$', $paymentAmount['pattern'] ?? null);

        $imports = $document['paths']['/api/v1/imports'] ?? null;
        self::assertIsArray($imports);
        self::assertArrayHasKey('get', $imports);
        self::assertArrayHasKey('post', $imports);
        self::assertSame('binary', $imports['post']['requestBody']['content']['multipart/form-data']['schema']['properties']['file']['format'] ?? null);
        $importResponse = $imports['post']['responses']['201']['content']['application/json']['schema'] ?? null;
        self::assertIsArray($importResponse);
        $batchProperties = $importResponse['properties']['data']['oneOf'][0]['properties'] ?? [];
        self::assertSame('integer', $batchProperties['id']['type'] ?? null);
        self::assertArrayNotHasKey('storage_key', $batchProperties);
        self::assertArrayNotHasKey('organization_id', $batchProperties);
        foreach (['mapping', 'validate', 'preview', 'process', 'errors', 'cancel'] as $operation) {
            self::assertArrayHasKey('/api/v1/imports/{id}/'.$operation, $document['paths']);
        }

        $financial = $document['paths']['/api/v1/customers/{id}/financial-summary'] ?? null;
        self::assertIsArray($financial);
        self::assertArrayHasKey('get', $financial);
        self::assertTrue($financial['get']['parameters'][1]['required'] ?? false);
        $financialData = $financial['get']['responses']['200']['content']['application/json']['schema']['properties']['data']['properties'] ?? [];
        self::assertSame('string', $financialData['exposure']['type'] ?? null);
        self::assertSame('string', $financialData['available_credit']['properties']['value']['oneOf'][0]['type'] ?? null);
        self::assertArrayNotHasKey('organization_id', $financialData);
        self::assertSame('string', $financialData['average_payment_delay_days']['properties']['value']['oneOf'][0]['type'] ?? null);
        self::assertSame('integer', $financialData['maximum_payment_delay_days']['properties']['value']['oneOf'][0]['type'] ?? null);
        $financialRequired = $financial['get']['responses']['200']['content']['application/json']['schema']['properties']['data']['required'] ?? [];
        self::assertContains('data_quality_score', $financialRequired);
        self::assertContains('last_data_update', $financialRequired);
    }
}
