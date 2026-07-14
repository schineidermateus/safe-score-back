<?php

declare(strict_types=1);

namespace App\Customers\Application\UseCase;

use App\Customers\Application\DTO\CustomerOutput;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class GetCustomer
{
    public function __construct(
        private CustomerRepository $repository,
        private CurrentOrganizationProviderInterface $currentOrganization,
    ) {
    }

    public function execute(int $customerId): CustomerOutput
    {
        $customer = $this->repository->findById(
            $this->currentOrganization->currentOrganization(),
            $customerId,
        );

        if (null === $customer) {
            throw new DomainException('CUSTOMER_NOT_FOUND', 'Cliente não encontrado.', 404);
        }

        return CustomerOutput::fromEntity($customer);
    }
}
