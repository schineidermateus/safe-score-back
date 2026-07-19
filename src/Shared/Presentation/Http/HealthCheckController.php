<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

final class HealthCheckController
{
    public function __invoke(): JsonResponse
    {
        return ApiResponseFactory::success([
            'status' => 'ok',
            'service' => 'stone-traceability-back',
        ]);
    }
}
