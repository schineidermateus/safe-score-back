<?php

declare(strict_types=1);

namespace App\Credit\Presentation\Http\Controller;

use App\Credit\Application\DTO\GetActiveCreditLimitInput;
use App\Credit\Application\UseCase\GetActiveCreditLimit;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetActiveCreditLimitController
{
    public function __construct(private GetActiveCreditLimit $getActiveCreditLimit)
    {
    }

    #[Route('/api/v1/customers/{customerId}/credit-limits/active', name: 'credit_limits_active', requirements: ['customerId' => '\\d+'], methods: ['GET'])]
    public function __invoke(
        int $customerId,
        #[MapQueryString] GetActiveCreditLimitInput $input = new GetActiveCreditLimitInput(),
    ): JsonResponse {
        $limit = $this->getActiveCreditLimit->execute($customerId, $input);

        return ApiResponseFactory::success($limit?->toArray());
    }
}
