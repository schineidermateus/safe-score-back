<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiResponse
{
    /**
     * @param array<string, mixed> $meta
     */
    public static function success(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        return new JsonResponse([
            'data' => $data,
            'meta' => (object) $meta,
            'errors' => [],
        ], $status);
    }

    /**
     * @param list<ApiError>       $errors
     * @param array<string, mixed> $meta
     */
    public static function error(array $errors, int $status, array $meta = []): JsonResponse
    {
        return new JsonResponse([
            'data' => null,
            'meta' => (object) $meta,
            'errors' => array_map(
                static fn (ApiError $error): array => $error->toArray(),
                $errors,
            ),
        ], $status);
    }
}
