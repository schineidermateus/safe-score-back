<?php

declare(strict_types=1);

namespace App\Customers\Application\UseCase;

use App\Customers\Application\DTO\CustomerListOutput;
use App\Customers\Application\DTO\CustomerOutput;
use App\Customers\Application\DTO\ListCustomersInput;
use App\Customers\Domain\Enum\CustomerStatus;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;

final readonly class ListCustomers
{
    public function __construct(
        private CustomerRepository $repository,
        private CurrentOrganizationProviderInterface $currentOrganization,
    ) {
    }

    public function execute(ListCustomersInput $input): CustomerListOutput
    {
        $organization = $this->currentOrganization->currentOrganization();
        $status = null === $input->status ? null : CustomerStatus::from($input->status);
        $customers = $this->repository->list(
            $organization,
            $input->search,
            $status,
            $input->page,
            $input->perPage,
            $input->sort,
        );

        return new CustomerListOutput(
            array_map(CustomerOutput::fromEntity(...), $customers),
            $input->page,
            $input->perPage,
            $this->repository->countMatching($organization, $input->search, $status),
        );
    }
}
