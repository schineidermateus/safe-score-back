<?php

declare(strict_types=1);

namespace App\Customers\Presentation\Http\Controller;

use App\Customers\Application\UseCase\GetCustomer;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final readonly class GetCustomerController
{
    public function __construct(private GetCustomer $getCustomer)
    {
    }

    #[Route('/api/v1/customers/{id}', name: 'customers_get', methods: ['GET'])]
    #[IsGranted('ROLE_ORGANIZATION_VIEWER')]
    public function __invoke(string $id): JsonResponse
    {
        return ApiResponseFactory::success($this->getCustomer->execute($id)->toArray());
    }
}
