<?php

declare(strict_types=1);

namespace App\Tests\Shared\Presentation\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HealthCheckTest extends WebTestCase
{
    public function testHealthCheckReturnsTheStandardEnvelope(): void
    {
        $client = self::createClient();
        $client->request('GET', '/health');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertJsonStringEqualsJsonString(
            <<<'JSON'
            {
                "data": {
                    "status": "ok",
                    "service": "stone-traceability-back"
                },
                "meta": {},
                "errors": []
            }
            JSON,
            (string) $client->getResponse()->getContent(),
        );
    }
}
