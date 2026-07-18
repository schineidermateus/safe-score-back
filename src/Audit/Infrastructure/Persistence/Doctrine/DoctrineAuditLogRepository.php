<?php

declare(strict_types=1);

namespace App\Audit\Infrastructure\Persistence\Doctrine;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<AuditLog> */
final class DoctrineAuditLogRepository extends ServiceEntityRepository implements AuditLogRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuditLog::class);
    }

    public function save(AuditLog $auditLog): void
    {
        $this->getEntityManager()->persist($auditLog);
    }
}
