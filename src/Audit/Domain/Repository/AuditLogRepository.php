<?php

declare(strict_types=1);

namespace App\Audit\Domain\Repository;

use App\Audit\Domain\Entity\AuditLog;

interface AuditLogRepository
{
    public function save(AuditLog $auditLog): void;
}
