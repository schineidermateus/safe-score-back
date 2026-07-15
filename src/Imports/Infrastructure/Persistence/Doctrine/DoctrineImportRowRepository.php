<?php

declare(strict_types=1);

namespace App\Imports\Infrastructure\Persistence\Doctrine;

use App\Imports\Domain\Entity\ImportBatch;
use App\Imports\Domain\Entity\ImportRow;
use App\Imports\Domain\Enum\ImportRowStatus;
use App\Imports\Domain\Repository\ImportRowRepository;
use App\Organizations\Domain\Entity\Organization;
use App\Shared\Domain\Exception\DomainException;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<ImportRow> */
final class DoctrineImportRowRepository extends ServiceEntityRepository implements ImportRowRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ImportRow::class);
    }

    public function save(Organization $organization, ImportRow $row): void
    {
        if ($row->batch()->organization() !== $organization) {
            throw new DomainException('IMPORT_TENANT_MISMATCH', 'Linha não pertence à organização atual.', 403);
        }
        $this->getEntityManager()->persist($row);
        $this->getEntityManager()->flush();
    }

    public function deleteByBatch(Organization $organization, ImportBatch $batch): void
    {
        $this->assertTenant($organization, $batch);
        $this->createQueryBuilder('row')->delete()->andWhere('row.batch = :batch')->setParameter('batch', $batch)->getQuery()->execute();
    }

    public function list(Organization $organization, ImportBatch $batch, int $page, int $perPage, ?ImportRowStatus $status = null): array
    {
        /** @var list<ImportRow> $result */ $result = $this->filtered($organization, $batch, $status)->orderBy('row.rowNumber', 'ASC')->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage)->getQuery()->getResult();

        return $result;
    }

    public function findValidForProcessing(Organization $organization, ImportBatch $batch): array
    {
        /** @var list<ImportRow> $result */ $result = $this->filtered($organization, $batch, ImportRowStatus::Valid)->orderBy('row.rowNumber', 'ASC')->getQuery()->getResult();

        return $result;
    }

    public function countMatching(Organization $organization, ImportBatch $batch, ?ImportRowStatus $status = null): int
    {
        return (int) $this->filtered($organization, $batch, $status)->select('COUNT(row.id)')->getQuery()->getSingleScalarResult();
    }

    public function listErrors(Organization $organization, ImportBatch $batch, int $page, int $perPage): array
    {
        /** @var list<ImportRow> $result */ $result = $this->errorQuery($organization, $batch)->orderBy('row.rowNumber', 'ASC')->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage)->getQuery()->getResult();

        return $result;
    }

    public function countErrors(Organization $organization, ImportBatch $batch): int
    {
        return (int) $this->errorQuery($organization, $batch)->select('COUNT(row.id)')->getQuery()->getSingleScalarResult();
    }

    private function filtered(Organization $organization, ImportBatch $batch, ?ImportRowStatus $status): QueryBuilder
    {
        $this->assertTenant($organization, $batch);
        $qb = $this->createQueryBuilder('row')->andWhere('row.batch = :batch')->setParameter('batch', $batch);
        if (null !== $status) {
            $qb->andWhere('row.status = :status')->setParameter('status', $status);
        }

        return $qb;
    }

    private function errorQuery(Organization $organization, ImportBatch $batch): QueryBuilder
    {
        return $this->filtered($organization, $batch, null)->andWhere('row.status IN (:statuses)')->setParameter('statuses', [ImportRowStatus::Invalid, ImportRowStatus::Failed]);
    }

    private function assertTenant(Organization $organization, ImportBatch $batch): void
    {
        if ($batch->organization() !== $organization) {
            throw new DomainException('IMPORT_TENANT_MISMATCH', 'Lote não pertence à organização atual.', 403);
        }
    }
}
