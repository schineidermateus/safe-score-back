<?php

declare(strict_types=1);

namespace App\Customers\Application\UseCase;

use App\Customers\Application\DTO\CustomerListOutput;
use App\Customers\Application\DTO\CustomerOutput;
use App\Customers\Application\DTO\ListCustomersInput;
use App\Customers\Domain\Enum\CustomerStatus;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Application\Context\OrganizationContextInterface;

final readonly class ListCustomers
{
    public function __construct(
        private CustomerRepository $repository,
        private OrganizationContextInterface $organizationContext,
    ) {
    }

    public function execute(ListCustomersInput $input): CustomerListOutput
    {
        $organizationId = $this->organizationContext->requireOrganizationId();
        $status = null === $input->status ? null : CustomerStatus::from($input->status);
        $customers = $this->repository->list(
            $organizationId,
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
            $this->repository->countMatching($organizationId, $input->search, $status),
        );
    }
}
