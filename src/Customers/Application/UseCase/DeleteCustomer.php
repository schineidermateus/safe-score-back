<?php

declare(strict_types=1);

namespace App\Customers\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class DeleteCustomer
{
    public function __construct(
        private CustomerRepository $repository,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private AuthorizationService $authorization,
    ) {
    }

    public function execute(int $customerId): void
    {
        $this->authorization->assertGranted(AuthorizationAction::ManageCustomers);
        $customer = $this->repository->findById(
            $this->currentOrganization->currentOrganization(),
            $customerId,
        );

        if (null === $customer) {
            throw new DomainException('CUSTOMER_NOT_FOUND', 'Cliente não encontrado.', 404);
        }

        $customer->delete(new \DateTimeImmutable());
        $this->repository->save($customer);
    }
}
