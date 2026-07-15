<?php

declare(strict_types=1);

namespace App\Credit\Presentation\Http\Controller;

use App\Credit\Application\DTO\CreateCreditLimitInput;
use App\Credit\Application\UseCase\CreateCreditLimit;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CreateCreditLimitController
{
    public function __construct(private CreateCreditLimit $createCreditLimit)
    {
    }

    #[Route('/api/v1/customers/{customerId}/credit-limits', name: 'credit_limits_create', requirements: ['customerId' => '\\d+'], methods: ['POST'])]
    public function __invoke(
        int $customerId,
        #[MapRequestPayload(serializationContext: ['allow_extra_attributes' => false])]
        CreateCreditLimitInput $input,
    ): JsonResponse {
        return ApiResponseFactory::success(
            $this->createCreditLimit->execute($customerId, $input)->toArray(),
            status: 201,
        );
    }
}
