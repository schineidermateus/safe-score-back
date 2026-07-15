<?php

declare(strict_types=1);

namespace App\Receivables\Presentation\Http\Controller;

use App\Receivables\Application\DTO\ListReceivablesInput;
use App\Receivables\Application\UseCase\ListReceivables;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListReceivablesController
{
    public function __construct(private ListReceivables $useCase)
    {
    }

    #[Route('/api/v1/receivables', name: 'receivables_list', methods: ['GET'])]
    public function __invoke(#[MapQueryString] ListReceivablesInput $input = new ListReceivablesInput()): JsonResponse
    {
        $result = $this->useCase->execute($input);

        return ApiResponseFactory::success(array_map(static fn ($item): array => $item->toArray(), $result->receivables),
            ['page' => $result->page, 'per_page' => $result->perPage, 'total' => $result->total, 'total_pages' => (int) ceil($result->total / $result->perPage)]);
    }
}
