<?php

declare(strict_types=1);

namespace App\Imports\Presentation\Http\Controller;

use App\Imports\Application\UseCase\ListImportBatches;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListImportBatchesController
{
    public function __construct(private ListImportBatches $list)
    {
    }

    #[Route('/api/v1/imports', name: 'imports_list', methods: ['GET'])]
    public function __invoke(Request $request): JsonResponse
    {
        $type = $request->query->getString('type');
        $status = $request->query->getString('status');
        $result = $this->list->execute('' === $type ? null : $type, '' === $status ? null : $status, $request->query->getInt('page', 1), $request->query->getInt('per_page', 20));

        return ApiResponseFactory::success($result['items'], ['pagination' => ['page' => $result['page'], 'per_page' => $result['per_page'], 'total' => $result['total']]]);
    }
}
