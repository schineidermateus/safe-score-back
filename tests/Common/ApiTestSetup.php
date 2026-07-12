<?php

namespace App\Tests\Common;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Shared\DataFixtures\AppFixtures;

class ApiTestSetup extends ApiTestCase
{
    protected function getBaseClient(): Client
    {
        return static::createClient([], [
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);
    }

    protected function getAuthenticatedClient(
        string $email = AppFixtures::EMAIL_FIXTURE,
        string $password = AppFixtures::PASSWORD_FIXTURE,
    ): Client {
        $client = $this->getBaseClient();

        $client->setDefaultOptions([
            'headers' => [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);

        return $client;
    }
}
