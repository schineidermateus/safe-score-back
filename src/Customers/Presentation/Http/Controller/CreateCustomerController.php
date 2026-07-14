<?php

declare(strict_types=1);

namespace App\Customers\Presentation\Http\Controller;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Customers\Application\DTO\CreateCustomerInput;
use App\Customers\Application\UseCase\CreateCustomer;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CreateCustomerController
{
    public function __construct(
        private CreateCustomer $createCustomer,
        private AuthorizationService $authorization,
    ) {
    }

    #[Route('/api/v1/customers', name: 'customers_create', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] CreateCustomerInput $input): JsonResponse
    {
        $this->authorization->assertGranted(AuthorizationAction::ManageCustomers);
        $customer = $this->createCustomer->execute($input);

        return ApiResponseFactory::success($customer->toArray(), status: 201);
    }
}
