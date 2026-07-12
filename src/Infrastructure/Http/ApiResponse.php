<?php

namespace App\Infrastructure\Http;

class ApiResponse
{
    public function __construct(
        public mixed $data,
        public int $status = 200,
        public array $headers = []
    ) {}
}
