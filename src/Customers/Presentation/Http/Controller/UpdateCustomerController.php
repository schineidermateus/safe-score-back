<?php

declare(strict_types=1);

namespace App\Customers\Presentation\Http\Controller;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Customers\Application\DTO\UpdateCustomerInput;
use App\Customers\Application\UseCase\UpdateCustomer;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateCustomerController
{
    public function __construct(
        private UpdateCustomer $updateCustomer,
        private AuthorizationService $authorization,
    ) {
    }

    #[Route('/api/v1/customers/{id}', name: 'customers_update', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function __invoke(int $id, #[MapRequestPayload] UpdateCustomerInput $input): JsonResponse
    {
        $this->authorization->assertGranted(AuthorizationAction::ManageCustomers);

        return ApiResponseFactory::success($this->updateCustomer->execute($id, $input)->toArray());
    }
}
