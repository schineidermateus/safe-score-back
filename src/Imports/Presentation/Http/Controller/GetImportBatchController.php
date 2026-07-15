<?php

declare(strict_types=1);

namespace App\Imports\Presentation\Http\Controller;

use App\Imports\Application\UseCase\GetImportBatch;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetImportBatchController
{
    public function __construct(private GetImportBatch $get)
    {
    }

    #[Route('/api/v1/imports/{id}', name: 'imports_get', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id): JsonResponse
    {
        return ApiResponseFactory::success($this->get->execute($id)->toArray());
    }
}
