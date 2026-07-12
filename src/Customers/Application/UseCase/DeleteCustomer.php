<?php

declare(strict_types=1);

namespace App\Customers\Application\UseCase;

use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Application\Context\OrganizationContextInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class DeleteCustomer
{
    public function __construct(
        private CustomerRepository $repository,
        private OrganizationContextInterface $organizationContext,
    ) {
    }

    public function execute(string $customerId): void
    {
        $customer = $this->repository->findById(
            $this->organizationContext->requireOrganizationId(),
            $customerId,
        );

        if (null === $customer) {
            throw new DomainException('CUSTOMER_NOT_FOUND', 'Cliente não encontrado.', 404);
        }

        $customer->delete(new \DateTimeImmutable());
        $this->repository->save($customer);
    }
}
