<?php

declare(strict_types=1);

namespace App\Imports\Presentation\Http\Controller;

use App\Imports\Application\UseCase\ProcessImportBatch;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ProcessImportBatchController
{
    public function __construct(private ProcessImportBatch $process)
    {
    }

    #[Route('/api/v1/imports/{id}/process', name: 'imports_process', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(int $id): JsonResponse
    {
        return ApiResponseFactory::success($this->process->execute($id)->toArray());
    }
}
