<?php

declare(strict_types=1);

namespace App\Receivables\Presentation\Http\Controller;

use App\Receivables\Application\UseCase\GetReceivable;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetReceivableController
{
    public function __construct(private GetReceivable $useCase)
    {
    }

    #[Route('/api/v1/receivables/{id}', name: 'receivables_get', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        return ApiResponseFactory::success($this->useCase->execute($id, $request->query->getString('reference_date') ?: null)->toArray());
    }
}
