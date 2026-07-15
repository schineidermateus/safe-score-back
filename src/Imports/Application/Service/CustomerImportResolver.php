<?php

declare(strict_types=1);

namespace App\Imports\Application\Service;

use App\Customers\Domain\Entity\Customer;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Domain\Entity\Organization;
use App\Shared\Domain\Exception\DomainException;

final readonly class CustomerImportResolver
{
    public function __construct(private CustomerRepository $customers)
    {
    }

    public function resolve(Organization $organization, ?string $externalId, ?string $document): ?Customer
    {
        $byExternal = null !== $externalId ? $this->customers->findByExternalId($organization, $externalId) : null;
        $byDocument = null !== $document ? $this->customers->findByDocument($organization, $document) : null;
        if (null !== $byExternal && null !== $byDocument && $byExternal->requireId() !== $byDocument->requireId()) {
            throw new DomainException('IMPORT_CUSTOMER_AMBIGUOUS', 'Identificador externo e documento apontam para clientes diferentes.', 422);
        }

        return $byExternal ?? $byDocument;
    }

    public function require(Organization $organization, ?string $externalId, ?string $document): Customer
    {
        return $this->resolve($organization, $externalId, $document)
            ?? throw new DomainException('IMPORT_CUSTOMER_NOT_FOUND', 'Cliente não encontrado na organização atual.', 422);
    }

    public function assertNoArchivedConflict(Organization $organization, ?string $externalId, ?string $document): void
    {
        if ((null !== $externalId && $this->customers->externalIdExists($organization, $externalId)) || (null !== $document && $this->customers->documentExists($organization, $document))) {
            throw new DomainException('IMPORT_CUSTOMER_ARCHIVED_OR_CONFLICT', 'A chave informada pertence a um cliente indisponível para importação.', 409);
        }
    }
}
