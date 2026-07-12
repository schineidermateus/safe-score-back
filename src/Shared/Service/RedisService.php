<?php

namespace App\Shared\Service;

use App\Shared\Interface\RedisInterface;

class RedisService implements RedisInterface
{
    private \Redis $client;

    public function __construct(string $host = 'redis', int $port = 6379)
    {
        $this->client = new \Redis();
        $this->client->connect($host, $port);
    }

    public function get(string $key): mixed
    {
        return $this->client->get($key);
    }

    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        return $ttl > 0 ?
            $this->client->setex($key, $ttl, $value) :
            $this->client->set($key, $value);
    }

    public function delete(string $key): bool
    {
        return $this->client->del($key) > 0;
    }

    public function exists(string $key): bool
    {
        return $this->client->exists($key) > 0;
    }
}
