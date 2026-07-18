<?php

declare(strict_types=1);

namespace App\Audit\Application;

use App\Audit\Domain\Entity\AuditLog;
use App\Audit\Domain\Repository\AuditLogRepository;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;

final readonly class AuditLogger
{
    public function __construct(private AuditLogRepository $repository)
    {
    }

    /**
     * @param array<string, mixed>|null $before
     * @param array<string, mixed>|null $after
     * @param array<string, mixed>|null $metadata
     */
    public function record(
        Organization $organization,
        User $user,
        string $action,
        string $entityType,
        int $entityId,
        ?array $before,
        ?array $after,
        ?array $metadata,
        \DateTimeImmutable $now,
    ): void {
        $this->repository->save(AuditLog::record(
            $organization,
            $user,
            $action,
            $entityType,
            $entityId,
            $before,
            $after,
            $metadata,
            $now,
        ));
    }
}
