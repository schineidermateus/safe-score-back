<?php

declare(strict_types=1);

namespace App\Customers\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Customers\Application\DTO\CreateCustomerInput;
use App\Customers\Application\DTO\CustomerOutput;
use App\Customers\Domain\Entity\Customer;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class CreateCustomer
{
    public function __construct(
        private CustomerRepository $repository,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private AuthorizationService $authorization,
    ) {
    }

    public function execute(CreateCustomerInput $input): CustomerOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::ManageCustomers);
        $organization = $this->currentOrganization->currentOrganization();
        $document = CustomerDocument::normalize($input->document);

        if (null !== $document && $this->repository->documentExists($organization, $document)) {
            throw new DomainException('CUSTOMER_DOCUMENT_ALREADY_EXISTS', 'Já existe um cliente com este documento.', 409, 'document');
        }

        $customer = Customer::create(
            $organization,
            $input->legalName,
            $input->tradeName,
            $document,
            $input->externalId,
            $input->segment,
            $input->accountManager,
            new \DateTimeImmutable(),
        );
        $this->repository->save($organization, $customer);

        return CustomerOutput::fromEntity($customer);
    }
}
