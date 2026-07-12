<?php

namespace App\Shared\Interface;

interface RedisInterface
{
    public function get(string $key): mixed;

    public function set(string $key, mixed $value, int $ttl = 0): bool;

    public function delete(string $key): bool;

    public function exists(string $key): bool;
}
