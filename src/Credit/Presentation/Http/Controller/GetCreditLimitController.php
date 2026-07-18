<?php

declare(strict_types=1);

namespace App\Credit\Presentation\Http\Controller;

use App\Credit\Application\UseCase\GetCreditLimit;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetCreditLimitController
{
    public function __construct(private GetCreditLimit $getCreditLimit)
    {
    }

    #[Route('/api/v1/credit-limits/{id}', name: 'credit_limits_get', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function __invoke(int $id): JsonResponse
    {
        return ApiResponseFactory::success($this->getCreditLimit->execute($id)->toArray());
    }
}
