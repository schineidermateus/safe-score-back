<?php

declare(strict_types=1);

namespace App\Customers\Application\UseCase;

use App\Customers\Application\DTO\CreateCustomerInput;
use App\Customers\Application\DTO\CustomerOutput;
use App\Customers\Domain\Entity\Customer;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Application\Context\OrganizationContextInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class CreateCustomer
{
    public function __construct(
        private CustomerRepository $repository,
        private OrganizationContextInterface $organizationContext,
    ) {
    }

    public function execute(CreateCustomerInput $input): CustomerOutput
    {
        $organizationId = $this->organizationContext->requireOrganizationId();
        $document = CustomerDocument::normalize($input->document);

        if (null !== $document && $this->repository->documentExists($organizationId, $document)) {
            throw new DomainException('CUSTOMER_DOCUMENT_ALREADY_EXISTS', 'Já existe um cliente com este documento.', 409, 'document');
        }

        $customer = Customer::create(
            $organizationId,
            $input->legalName,
            $input->tradeName,
            $document,
            $input->externalId,
            $input->segment,
            $input->accountManager,
            new \DateTimeImmutable(),
        );
        $this->repository->save($customer);

        return CustomerOutput::fromEntity($customer);
    }
}
