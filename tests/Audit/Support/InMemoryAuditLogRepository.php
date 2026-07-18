<?php

declare(strict_types=1);

namespace App\Tests\Audit\Support;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepository;
use App\Tests\Support\EntityId;

final class InMemoryAuditLogRepository implements AuditLogRepository
{
    /** @var list<AuditLog> */
    private array $items = [];

    public function save(AuditLog $auditLog): void
    {
        EntityId::assign($auditLog, count($this->items) + 1);
        $this->items[] = $auditLog;
    }

    /** @return list<AuditLog> */
    public function all(): array
    {
        return $this->items;
    }
}
