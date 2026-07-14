<?php

declare(strict_types=1);

namespace App\Customers\Presentation\Http\Controller;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Customers\Application\DTO\ListCustomersInput;
use App\Customers\Application\UseCase\ListCustomers;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListCustomersController
{
    public function __construct(
        private ListCustomers $listCustomers,
        private AuthorizationService $authorization,
    ) {
    }

    #[Route('/api/v1/customers', name: 'customers_list', methods: ['GET'])]
    public function __invoke(#[MapQueryString] ListCustomersInput $input = new ListCustomersInput()): JsonResponse
    {
        $this->authorization->assertGranted(AuthorizationAction::ViewData);
        $result = $this->listCustomers->execute($input);

        return ApiResponseFactory::success(
            array_map(
                static fn ($customer): array => $customer->toArray(),
                $result->customers,
            ),
            [
                'page' => $result->page,
                'per_page' => $result->perPage,
                'total' => $result->total,
                'total_pages' => (int) ceil($result->total / $result->perPage),
            ],
        );
    }
}
