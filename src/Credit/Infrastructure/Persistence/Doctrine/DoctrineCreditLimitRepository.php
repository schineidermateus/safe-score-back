<?php

declare(strict_types=1);

namespace App\Credit\Infrastructure\Persistence\Doctrine;

use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\Enum\CreditLimitStatus;
use App\Credit\Domain\Repository\CreditLimitRepository;
use App\Customers\Domain\Entity\Customer;
use App\Organizations\Domain\Entity\Organization;
use App\Shared\Domain\Exception\DomainException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<CreditLimit> */
final class DoctrineCreditLimitRepository extends ServiceEntityRepository implements CreditLimitRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditLimit::class);
    }

    public function save(Organization $organization, CreditLimit $creditLimit): void
    {
        if ($creditLimit->organization() !== $organization || $creditLimit->customer()->organization() !== $organization) {
            throw new DomainException('CREDIT_LIMIT_TENANT_MISMATCH', 'Limite de crédito não pertence à organização atual.', 403);
        }

        $this->getEntityManager()->persist($creditLimit);
        $this->getEntityManager()->flush();
    }

    public function findByIdAndOrganization(int $id, Organization $organization): ?CreditLimit
    {
        return $this->findOneBy(['id' => $id, 'organization' => $organization]);
    }

    public function findHistoryByCustomerAndOrganization(
        Customer $customer,
        Organization $organization,
        int $page,
        int $perPage,
    ): array {
        /** @var list<CreditLimit> $limits */
        $limits = $this->createQueryBuilder('creditLimit')
            ->addSelect('approvedBy')
            ->leftJoin('creditLimit.approvedBy', 'approvedBy')
            ->andWhere('creditLimit.organization = :organization')
            ->andWhere('creditLimit.customer = :customer')
            ->setParameter('organization', $organization)
            ->setParameter('customer', $customer)
            ->orderBy('creditLimit.createdAt', 'DESC')
            ->addOrderBy('creditLimit.id', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();

        return $limits;
    }

    public function countHistoryByCustomerAndOrganization(Customer $customer, Organization $organization): int
    {
        return (int) $this->createQueryBuilder('creditLimit')
            ->select('COUNT(creditLimit.id)')
            ->andWhere('creditLimit.organization = :organization')
            ->andWhere('creditLimit.customer = :customer')
            ->setParameter('organization', $organization)
            ->setParameter('customer', $customer)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findActiveByCustomerAndDate(
        Customer $customer,
        Organization $organization,
        \DateTimeImmutable $referenceDate,
    ): array {
        /** @var list<CreditLimit> $limits */
        $limits = $this->createQueryBuilder('creditLimit')
            ->andWhere('creditLimit.organization = :organization')
            ->andWhere('creditLimit.customer = :customer')
            ->andWhere('creditLimit.status = :status')
            ->andWhere('creditLimit.validFrom <= :referenceDate')
            ->andWhere('(creditLimit.validUntil IS NULL OR creditLimit.validUntil >= :referenceDate)')
            ->setParameter('organization', $organization)
            ->setParameter('customer', $customer)
            ->setParameter('status', CreditLimitStatus::Active)
            ->setParameter('referenceDate', $referenceDate)
            ->orderBy('creditLimit.validFrom', 'DESC')
            ->setMaxResults(2)
            ->getQuery()
            ->getResult();

        return $limits;
    }

    public function existsOverlappingActivePeriod(
        Customer $customer,
        Organization $organization,
        \DateTimeImmutable $validFrom,
        ?\DateTimeImmutable $validUntil,
        ?int $exceptCreditLimitId = null,
    ): bool {
        $queryBuilder = $this->createQueryBuilder('creditLimit')
            ->select('COUNT(creditLimit.id)')
            ->andWhere('creditLimit.organization = :organization')
            ->andWhere('creditLimit.customer = :customer')
            ->andWhere('creditLimit.status = :status')
            ->andWhere('(creditLimit.validUntil IS NULL OR creditLimit.validUntil >= :validFrom)')
            ->setParameter('organization', $organization)
            ->setParameter('customer', $customer)
            ->setParameter('status', CreditLimitStatus::Active)
            ->setParameter('validFrom', $validFrom);

        if (null !== $validUntil) {
            $queryBuilder
                ->andWhere('creditLimit.validFrom <= :validUntil')
                ->setParameter('validUntil', $validUntil);
        }
        if (null !== $exceptCreditLimitId) {
            $queryBuilder
                ->andWhere('creditLimit.id != :exceptCreditLimitId')
                ->setParameter('exceptCreditLimitId', $exceptCreditLimitId);
        }

        return (int) $queryBuilder->getQuery()->getSingleScalarResult() > 0;
    }

    public function lockCustomer(Customer $customer, Organization $organization): void
    {
        if ($customer->organization() !== $organization) {
            throw new DomainException('CUSTOMER_NOT_FOUND', 'Cliente não encontrado.', 404);
        }

        $this->getEntityManager()->lock($customer, LockMode::PESSIMISTIC_WRITE);
    }
}
