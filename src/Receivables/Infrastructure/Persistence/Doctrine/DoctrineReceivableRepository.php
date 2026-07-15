<?php

declare(strict_types=1);

namespace App\Receivables\Infrastructure\Persistence\Doctrine;

use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Enum\AgingBucket;
use App\Receivables\Domain\Enum\ReceivableStatus;
use App\Receivables\Domain\Repository\ReceivableCriteria;
use App\Receivables\Domain\Repository\ReceivableRepository;
use App\Shared\Domain\Exception\DomainException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Receivable> */
final class DoctrineReceivableRepository extends ServiceEntityRepository implements ReceivableRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Receivable::class);
    }

    public function save(Organization $organization, Receivable $receivable): void
    {
        if ($receivable->organization() !== $organization || $receivable->customer()->organization() !== $organization) {
            throw new DomainException('RECEIVABLE_TENANT_MISMATCH', 'Recebível não pertence à organização atual.', 403);
        }
        try {
            $this->getEntityManager()->persist($receivable);
            $this->getEntityManager()->flush();
        } catch (UniqueConstraintViolationException) {
            throw new DomainException('RECEIVABLE_DUPLICATE_EXTERNAL_KEY', 'A chave externa já existe nesta origem.', 409, 'external_id');
        }
    }

    public function findByIdAndOrganization(int $id, Organization $organization): ?Receivable
    {
        return $this->findOneBy(['id' => $id, 'organization' => $organization]);
    }

    public function findByIdAndOrganizationForUpdate(int $id, Organization $organization): ?Receivable
    {
        $receivable = $this->findByIdAndOrganization($id, $organization);
        if (null !== $receivable) {
            $this->getEntityManager()->lock($receivable, LockMode::PESSIMISTIC_WRITE);
        }

        return $receivable;
    }

    public function existsByExternalKey(Organization $organization, string $source, string $externalId, ?int $exceptId = null): bool
    {
        $qb = $this->createQueryBuilder('receivable')
            ->select('COUNT(receivable.id)')
            ->andWhere('receivable.organization = :organization')
            ->andWhere('receivable.source = :source')
            ->andWhere('receivable.externalId = :externalId')
            ->setParameter('organization', $organization)
            ->setParameter('source', $source)
            ->setParameter('externalId', $externalId);
        if (null !== $exceptId) {
            $qb->andWhere('receivable.id != :exceptId')->setParameter('exceptId', $exceptId);
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function list(Organization $organization, ReceivableCriteria $criteria): array
    {
        $queryBuilder = $this->matchingQuery($organization, $criteria)->addSelect('customer');
        if (!in_array('customer', $queryBuilder->getAllAliases(), true)) {
            $queryBuilder->leftJoin('receivable.customer', 'customer');
        }
        /** @var list<Receivable> $result */
        $result = $queryBuilder
            ->setFirstResult(($criteria->page - 1) * $criteria->perPage)
            ->setMaxResults($criteria->perPage)
            ->getQuery()->getResult();

        return $result;
    }

    public function countMatching(Organization $organization, ReceivableCriteria $criteria): int
    {
        return (int) $this->matchingQuery($organization, $criteria)
            ->select('COUNT(receivable.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function matchingQuery(Organization $organization, ReceivableCriteria $criteria): QueryBuilder
    {
        $qb = $this->createQueryBuilder('receivable')
            ->andWhere('receivable.organization = :organization')
            ->setParameter('organization', $organization);
        if (null !== $criteria->customerId) {
            $qb->andWhere('IDENTITY(receivable.customer) = :customerId')->setParameter('customerId', $criteria->customerId);
        }
        if (null !== $criteria->dueDateFrom) {
            $qb->andWhere('receivable.dueDate >= :dueFrom')->setParameter('dueFrom', $criteria->dueDateFrom);
        }
        if (null !== $criteria->dueDateTo) {
            $qb->andWhere('receivable.dueDate <= :dueTo')->setParameter('dueTo', $criteria->dueDateTo);
        }
        if (null !== $criteria->amountMin) {
            $qb->andWhere('receivable.openAmount >= :amountMin')->setParameter('amountMin', $criteria->amountMin);
        }
        if (null !== $criteria->amountMax) {
            $qb->andWhere('receivable.openAmount <= :amountMax')->setParameter('amountMax', $criteria->amountMax);
        }
        if (null !== $criteria->search) {
            if (!in_array('customer', $qb->getAllAliases(), true)) {
                $qb->leftJoin('receivable.customer', 'customer');
            }
            $qb->andWhere('(LOWER(receivable.documentNumber) LIKE :search OR LOWER(receivable.externalId) LIKE :search OR LOWER(customer.legalName) LIKE :search OR LOWER(customer.tradeName) LIKE :search)')
                ->setParameter('search', '%'.mb_strtolower($criteria->search).'%');
        }
        $this->applyStatus($qb, $criteria);
        $this->applyAging($qb, $criteria);
        [$field, $direction] = match ($criteria->sort) {
            '-due_date' => ['receivable.dueDate', 'DESC'],
            'created_at' => ['receivable.createdAt', 'ASC'],
            '-created_at' => ['receivable.createdAt', 'DESC'],
            'open_amount' => ['receivable.openAmount', 'ASC'],
            '-open_amount' => ['receivable.openAmount', 'DESC'],
            default => ['receivable.dueDate', 'ASC'],
        };
        $qb->orderBy($field, $direction)->addOrderBy('receivable.id', $direction);

        return $qb;
    }

    private function applyStatus(QueryBuilder $qb, ReceivableCriteria $criteria): void
    {
        $status = $criteria->status;
        if (true === $criteria->overdue) {
            $qb->andWhere('receivable.status != :cancelled')->andWhere('receivable.openAmount > 0')
                ->andWhere('receivable.dueDate < :referenceDate')
                ->setParameter('cancelled', ReceivableStatus::Cancelled)->setParameter('referenceDate', $criteria->referenceDate);
        } elseif (false === $criteria->overdue) {
            $qb->andWhere('(receivable.status = :notOverdueCancelled OR receivable.openAmount = 0 OR receivable.dueDate >= :referenceDate)')
                ->setParameter('notOverdueCancelled', ReceivableStatus::Cancelled)
                ->setParameter('referenceDate', $criteria->referenceDate);
        }
        if (null === $status) {
            return;
        }
        if (ReceivableStatus::Overdue === $status) {
            $qb->andWhere('receivable.status != :cancelled')->andWhere('receivable.openAmount > 0')
                ->andWhere('receivable.dueDate < :referenceDate')
                ->setParameter('cancelled', ReceivableStatus::Cancelled)->setParameter('referenceDate', $criteria->referenceDate);
        } elseif (in_array($status, [ReceivableStatus::Open, ReceivableStatus::PartiallyPaid], true)) {
            $qb->andWhere('receivable.status = :status')->andWhere('receivable.dueDate >= :referenceDate')
                ->setParameter('status', $status)->setParameter('referenceDate', $criteria->referenceDate);
        } else {
            $qb->andWhere('receivable.status = :status')->setParameter('status', $status);
        }
    }

    private function applyAging(QueryBuilder $qb, ReceivableCriteria $criteria): void
    {
        if (null === $criteria->agingBucket) {
            return;
        }
        $qb->andWhere('receivable.status != :agingCancelled')->andWhere('receivable.openAmount > 0')
            ->setParameter('agingCancelled', ReceivableStatus::Cancelled);
        $reference = $criteria->referenceDate;
        if (AgingBucket::Upcoming === $criteria->agingBucket) {
            $qb->andWhere('receivable.dueDate >= :agingReference')->setParameter('agingReference', $reference);

            return;
        }
        [$min, $max] = match ($criteria->agingBucket) {
            AgingBucket::Days1To15 => [1, 15], AgingBucket::Days16To30 => [16, 30],
            AgingBucket::Days31To60 => [31, 60], AgingBucket::Days61To90 => [61, 90],
            AgingBucket::Over90 => [91, null],
        };
        $qb->andWhere('receivable.dueDate <= :agingMax')->setParameter('agingMax', $reference->modify(sprintf('-%d days', $min)));
        if (null !== $max) {
            $qb->andWhere('receivable.dueDate >= :agingMin')->setParameter('agingMin', $reference->modify(sprintf('-%d days', $max)));
        }
    }
}
