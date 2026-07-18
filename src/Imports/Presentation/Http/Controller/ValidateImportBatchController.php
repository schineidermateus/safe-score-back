<?php

declare(strict_types=1);

namespace App\Imports\Presentation\Http\Controller;

use App\Imports\Application\UseCase\ValidateImportBatch;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ValidateImportBatchController
{
    public function __construct(private ValidateImportBatch $validate)
    {
    }

    #[Route('/api/v1/imports/{id}/validate', name: 'imports_validate', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(int $id): JsonResponse
    {
        return ApiResponseFactory::success($this->validate->execute($id)->toArray());
    }
}
