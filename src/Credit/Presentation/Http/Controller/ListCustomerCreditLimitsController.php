<?php

declare(strict_types=1);

namespace App\Credit\Presentation\Http\Controller;

use App\Credit\Application\DTO\ListCreditLimitsInput;
use App\Credit\Application\UseCase\ListCustomerCreditLimitHistory;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListCustomerCreditLimitsController
{
    public function __construct(private ListCustomerCreditLimitHistory $listCreditLimits)
    {
    }

    #[Route('/api/v1/customers/{customerId}/credit-limits', name: 'credit_limits_history', requirements: ['customerId' => '\\d+'], methods: ['GET'])]
    public function __invoke(
        int $customerId,
        #[MapQueryString] ListCreditLimitsInput $input = new ListCreditLimitsInput(),
    ): JsonResponse {
        $result = $this->listCreditLimits->execute($customerId, $input);

        return ApiResponseFactory::success(
            array_map(static fn ($limit): array => $limit->toArray(), $result->creditLimits),
            [
                'page' => $result->page,
                'per_page' => $result->perPage,
                'total' => $result->total,
                'total_pages' => (int) ceil($result->total / $result->perPage),
            ],
        );
    }
}
