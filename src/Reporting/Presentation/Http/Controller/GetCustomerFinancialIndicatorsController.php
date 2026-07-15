<?php

declare(strict_types=1);

namespace App\Reporting\Presentation\Http\Controller;

use App\Reporting\Application\DTO\GetCustomerFinancialIndicatorsInput;
use App\Reporting\Application\UseCase\GetCustomerFinancialIndicators;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetCustomerFinancialIndicatorsController
{
    public function __construct(private GetCustomerFinancialIndicators $getIndicators)
    {
    }

    #[Route('/api/v1/customers/{id}/financial-summary', name: 'customer_financial_indicators_get', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function __invoke(
        int $id,
        #[MapQueryString] GetCustomerFinancialIndicatorsInput $input = new GetCustomerFinancialIndicatorsInput(),
    ): JsonResponse {
        return ApiResponseFactory::success($this->getIndicators->execute($id, $input)->toArray());
    }
}
