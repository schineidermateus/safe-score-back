<?php

declare(strict_types=1);

namespace App\Imports\Presentation\Http\Controller;

use App\Imports\Application\UseCase\CancelImportBatch;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CancelImportBatchController
{
    public function __construct(private CancelImportBatch $cancel)
    {
    }

    #[Route('/api/v1/imports/{id}/cancel', name: 'imports_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(int $id): JsonResponse
    {
        return ApiResponseFactory::success($this->cancel->execute($id)->toArray());
    }
}
