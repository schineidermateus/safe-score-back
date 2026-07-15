<?php

declare(strict_types=1);

namespace App\Credit\Presentation\Http\Controller;

use App\Credit\Application\DTO\UpdateCreditLimitInput;
use App\Credit\Application\UseCase\UpdateCreditLimit;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateCreditLimitController
{
    public function __construct(private UpdateCreditLimit $updateCreditLimit)
    {
    }

    #[Route('/api/v1/credit-limits/{id}', name: 'credit_limits_update', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function __invoke(
        int $id,
        #[MapRequestPayload(serializationContext: ['allow_extra_attributes' => false])]
        UpdateCreditLimitInput $input,
    ): JsonResponse {
        return ApiResponseFactory::success($this->updateCreditLimit->execute($id, $input)->toArray());
    }
}
