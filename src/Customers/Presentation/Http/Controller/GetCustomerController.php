<?php

declare(strict_types=1);

namespace App\Customers\Presentation\Http\Controller;

use App\Customers\Application\UseCase\GetCustomer;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetCustomerController
{
    public function __construct(private GetCustomer $getCustomer)
    {
    }

    #[Route('/api/v1/customers/{id}', name: 'customers_get', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function __invoke(int $id): JsonResponse
    {
        return ApiResponseFactory::success($this->getCustomer->execute($id)->toArray());
    }
}
