<?php

declare(strict_types=1);

namespace App\Customers\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Customers\Application\DTO\CustomerOutput;
use App\Customers\Application\DTO\UpdateCustomerInput;
use App\Customers\Domain\Enum\CustomerStatus;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class UpdateCustomer
{
    public function __construct(
        private CustomerRepository $repository,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private AuthorizationService $authorization,
    ) {
    }

    public function execute(int $customerId, UpdateCustomerInput $input): CustomerOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::ManageCustomers);
        $organization = $this->currentOrganization->currentOrganization();
        $customer = $this->repository->findById($organization, $customerId);

        if (null === $customer) {
            throw new DomainException('CUSTOMER_NOT_FOUND', 'Cliente não encontrado.', 404);
        }

        $document = CustomerDocument::normalize($input->document);
        if (
            null !== $document
            && $this->repository->documentExists($organization, $document, $customer->requireId())
        ) {
            throw new DomainException('CUSTOMER_DOCUMENT_ALREADY_EXISTS', 'Já existe um cliente com este documento.', 409, 'document');
        }

        if (null !== $input->externalId && '' !== trim($input->externalId) && $this->repository->externalIdExists($organization, trim($input->externalId), $customer->requireId())) {
            throw new DomainException('CUSTOMER_EXTERNAL_ID_ALREADY_EXISTS', 'Já existe um cliente com este identificador externo.', 409, 'external_id');
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
        $this->repository->save($organization, $customer);

        return CustomerOutput::fromEntity($customer);
    }
}
