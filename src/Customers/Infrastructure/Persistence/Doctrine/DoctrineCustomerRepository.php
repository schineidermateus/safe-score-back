<?php

declare(strict_types=1);

namespace App\Customers\Infrastructure\Persistence\Doctrine;

use App\Customers\Domain\Entity\Customer;
use App\Customers\Domain\Enum\CustomerStatus;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Domain\Entity\Organization;
use App\Shared\Domain\Exception\DomainException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Customer>
 */
final class DoctrineCustomerRepository extends ServiceEntityRepository implements CustomerRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function save(Organization $organization, Customer $customer): void
    {
        if ($customer->organization() !== $organization) {
            throw new DomainException('CUSTOMER_TENANT_MISMATCH', 'Cliente não pertence à organização atual.', 403);
        }

        $entityManager = $this->getEntityManager();
        $entityManager->persist($customer);

        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            throw new DomainException('CUSTOMER_DOCUMENT_ALREADY_EXISTS', 'Já existe um cliente com este documento.', 409, 'document');
        }
    }

    public function findById(Organization $organization, int $customerId): ?Customer
    {
        return $this->createQueryBuilder('customer')
            ->andWhere('customer.organization = :organization')
            ->andWhere('customer.id = :customerId')
            ->andWhere('customer.deletedAt IS NULL')
            ->setParameter('organization', $organization)
            ->setParameter('customerId', $customerId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function documentExists(
        Organization $organization,
        string $document,
        ?int $exceptCustomerId = null,
    ): bool {
        $queryBuilder = $this->createQueryBuilder('customer')
            ->select('COUNT(customer.id)')
            ->andWhere('customer.organization = :organization')
            ->andWhere('customer.document = :document')
            ->setParameter('organization', $organization)
            ->setParameter('document', $document);

        if (null !== $exceptCustomerId) {
            $queryBuilder
                ->andWhere('customer.id != :exceptCustomerId')
                ->setParameter('exceptCustomerId', $exceptCustomerId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    public function list(
        Organization $organization,
        ?string $search,
        ?CustomerStatus $status,
        int $page,
        int $perPage,
        string $sort,
    ): array {
        $queryBuilder = $this->filteredQuery($organization, $search, $status);
        [$field, $direction] = $this->sort($sort);

        /** @var list<Customer> $customers */
        $customers = $queryBuilder
            ->orderBy('customer.'.$field, $direction)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return $customers;
    }

    public function countMatching(
        Organization $organization,
        ?string $search,
        ?CustomerStatus $status,
    ): int {
        return (int) $this->filteredQuery($organization, $search, $status)
            ->select('COUNT(customer.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function filteredQuery(
        Organization $organization,
        ?string $search,
        ?CustomerStatus $status,
    ): QueryBuilder {
        $queryBuilder = $this->createQueryBuilder('customer')
            ->andWhere('customer.organization = :organization')
            ->andWhere('customer.deletedAt IS NULL')
            ->setParameter('organization', $organization);

        if (null !== $search && '' !== trim($search)) {
            $queryBuilder
                ->andWhere(
                    'LOWER(customer.legalName) LIKE :search
                    OR LOWER(customer.tradeName) LIKE :search
                    OR customer.document LIKE :search
                    OR LOWER(customer.externalId) LIKE :search',
                )
                ->setParameter('search', '%'.mb_strtolower(trim($search)).'%');
        }

        if (null !== $status) {
            $queryBuilder
                ->andWhere('customer.status = :status')
                ->setParameter('status', $status);
        }

        return $queryBuilder;
    }

    /**
     * @return array{string, 'ASC'|'DESC'}
     */
    private function sort(string $sort): array
    {
        return match ($sort) {
            'legal_name' => ['legalName', 'ASC'],
            '-legal_name' => ['legalName', 'DESC'],
            'created_at' => ['createdAt', 'ASC'],
            '-created_at' => ['createdAt', 'DESC'],
            default => ['legalName', 'ASC'],
        };
    }
}
