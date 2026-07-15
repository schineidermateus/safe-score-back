<?php

declare(strict_types=1);

namespace App\Imports\Presentation\Http\Controller;

use App\Imports\Application\UseCase\GetImportPreview;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetImportPreviewController
{
    public function __construct(private GetImportPreview $preview)
    {
    }

    #[Route('/api/v1/imports/{id}/preview', name: 'imports_preview', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $result = $this->preview->execute($id, $request->query->getInt('page', 1), $request->query->getInt('per_page', 100));

        return ApiResponseFactory::success($result['items'], ['pagination' => ['page' => $result['page'], 'per_page' => $result['per_page'], 'total' => $result['total']]]);
    }
}
