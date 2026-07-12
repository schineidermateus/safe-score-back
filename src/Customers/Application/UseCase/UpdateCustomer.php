<?php

declare(strict_types=1);

namespace App\Customers\Application\UseCase;

use App\Customers\Application\DTO\CustomerOutput;
use App\Customers\Application\DTO\UpdateCustomerInput;
use App\Customers\Domain\Enum\CustomerStatus;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Application\Context\OrganizationContextInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class UpdateCustomer
{
    public function __construct(
        private CustomerRepository $repository,
        private OrganizationContextInterface $organizationContext,
    ) {
    }

    public function execute(string $customerId, UpdateCustomerInput $input): CustomerOutput
    {
        $organizationId = $this->organizationContext->requireOrganizationId();
        $customer = $this->repository->findById($organizationId, $customerId);

        if (null === $customer) {
            throw new DomainException('CUSTOMER_NOT_FOUND', 'Cliente não encontrado.', 404);
        }

        $document = CustomerDocument::normalize($input->document);
        if (
            null !== $document
            && $this->repository->documentExists($organizationId, $document, $customer->id())
        ) {
            throw new DomainException('CUSTOMER_DOCUMENT_ALREADY_EXISTS', 'Já existe um cliente com este documento.', 409, 'document');
        }

        $customer->update(
            $input->legalName,
            $input->tradeName,
            $document,
            $input->externalId,
            $input->segment,
            $input->accountManager,
            CustomerStatus::from($input->status),
            new \DateTimeImmutable(),
        );
        $this->repository->save($customer);

        return CustomerOutput::fromEntity($customer);
    }
}
