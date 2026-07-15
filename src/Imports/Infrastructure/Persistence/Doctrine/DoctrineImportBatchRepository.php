<?php

declare(strict_types=1);

namespace App\Imports\Infrastructure\Persistence\Doctrine;

use App\Imports\Domain\Entity\ImportBatch;
use App\Imports\Domain\Enum\ImportBatchStatus;
use App\Imports\Domain\Enum\ImportType;
use App\Imports\Domain\Repository\ImportBatchRepository;
use App\Organizations\Domain\Entity\Organization;
use App\Shared\Domain\Exception\DomainException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ImportBatch> */
final class DoctrineImportBatchRepository extends ServiceEntityRepository implements ImportBatchRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportBatch::class);
    }

    public function save(Organization $organization, ImportBatch $batch): void
    {
        if ($batch->organization() !== $organization) {
            throw new DomainException('IMPORT_TENANT_MISMATCH', 'Lote não pertence à organização atual.', 403);
        }
        $this->getEntityManager()->persist($batch);
        $this->getEntityManager()->flush();
    }

    public function findById(Organization $organization, int $id): ?ImportBatch
    {
        return $this->findOneBy(['id' => $id, 'organization' => $organization]);
    }

    public function findByIdForUpdate(Organization $organization, int $id): ?ImportBatch
    {
        return $this->createQueryBuilder('batch')
            ->andWhere('batch.organization = :organization')
            ->andWhere('batch.id = :id')
            ->setParameter('organization', $organization)
            ->setParameter('id', $id)
            ->getQuery()
            ->setLockMode(LockMode::PESSIMISTIC_WRITE)
            ->getOneOrNullResult();
    }

    public function findCompletedByHash(Organization $organization, ImportType $type, string $hash): ?ImportBatch
    {
        return $this->createQueryBuilder('batch')->andWhere('batch.organization = :organization')->andWhere('batch.type = :type')->andWhere('batch.fileHash = :hash')->andWhere('batch.status IN (:statuses)')->setParameter('organization', $organization)->setParameter('type', $type)->setParameter('hash', $hash)->setParameter('statuses', [ImportBatchStatus::Completed, ImportBatchStatus::CompletedWithErrors])->orderBy('batch.id', 'DESC')->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    public function list(Organization $organization, ?ImportType $type, ?ImportBatchStatus $status, int $page, int $perPage): array
    {
        /** @var list<ImportBatch> $result */ $result = $this->filtered($organization, $type, $status)->orderBy('batch.createdAt', 'DESC')->addOrderBy('batch.id', 'DESC')->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage)->getQuery()->getResult();

        return $result;
    }

    public function countMatching(Organization $organization, ?ImportType $type, ?ImportBatchStatus $status): int
    {
        return (int) $this->filtered($organization, $type, $status)->select('COUNT(batch.id)')->getQuery()->getSingleScalarResult();
    }

    private function filtered(Organization $organization, ?ImportType $type, ?ImportBatchStatus $status): QueryBuilder
    {
        $qb = $this->createQueryBuilder('batch')->andWhere('batch.organization = :organization')->setParameter('organization', $organization);
        if (null !== $type) {
            $qb->andWhere('batch.type = :type')->setParameter('type', $type);
        }
        if (null !== $status) {
            $qb->andWhere('batch.status = :status')->setParameter('status', $status);
        }

        return $qb;
    }
}
