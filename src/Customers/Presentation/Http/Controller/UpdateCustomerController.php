<?php

declare(strict_types=1);

namespace App\Customers\Presentation\Http\Controller;

use App\Customers\Application\DTO\UpdateCustomerInput;
use App\Customers\Application\UseCase\UpdateCustomer;
use App\Shared\Presentation\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final readonly class UpdateCustomerController
{
    public function __construct(private UpdateCustomer $updateCustomer)
    {
    }

    #[Route('/api/v1/customers/{id}', name: 'customers_update', methods: ['PATCH'])]
    #[IsGranted('ROLE_ORGANIZATION_ANALYST')]
    public function __invoke(string $id, #[MapRequestPayload] UpdateCustomerInput $input): JsonResponse
    {
        return ApiResponse::success($this->updateCustomer->execute($id, $input)->toArray());
    }
}
