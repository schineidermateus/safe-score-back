<?php

declare(strict_types=1);

namespace App\Customers\Presentation\Http\Controller;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Customers\Application\UseCase\DeleteCustomer;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final readonly class DeleteCustomerController
{
    public function __construct(
        private DeleteCustomer $deleteCustomer,
        private AuthorizationService $authorization,
    ) {
    }

    #[Route('/api/v1/customers/{id}', name: 'customers_delete', requirements: ['id' => '\\d+'], methods: ['DELETE'])]
    public function __invoke(int $id): Response
    {
        $this->authorization->assertGranted(AuthorizationAction::ManageCustomers);
        $this->deleteCustomer->execute($id);

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
