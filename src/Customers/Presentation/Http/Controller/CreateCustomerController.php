<?php

declare(strict_types=1);

namespace App\Customers\Presentation\Http\Controller;

use App\Customers\Application\DTO\CreateCustomerInput;
use App\Customers\Application\UseCase\CreateCustomer;
use App\Shared\Presentation\Http\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final readonly class CreateCustomerController
{
    public function __construct(private CreateCustomer $createCustomer)
    {
    }

    #[Route('/api/v1/customers', name: 'customers_create', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANIZATION_ANALYST')]
    public function __invoke(#[MapRequestPayload] CreateCustomerInput $input): JsonResponse
    {
        $customer = $this->createCustomer->execute($input);

        return ApiResponse::success($customer->toArray(), status: 201);
    }
}
