<?php

declare(strict_types=1);

namespace App\Credit\Presentation\Http\Controller;

use App\Credit\Application\DTO\RevokeCreditLimitInput;
use App\Credit\Application\UseCase\RevokeCreditLimit;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class RevokeCreditLimitController
{
    public function __construct(private RevokeCreditLimit $revokeCreditLimit)
    {
    }

    #[Route('/api/v1/credit-limits/{id}/revoke', name: 'credit_limits_revoke', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function __invoke(
        int $id,
        #[MapRequestPayload(serializationContext: ['allow_extra_attributes' => false])]
        RevokeCreditLimitInput $input,
    ): JsonResponse {
        return ApiResponseFactory::success($this->revokeCreditLimit->execute($id, $input)->toArray());
    }
}
