<?php

declare(strict_types=1);

namespace App\Audit\Domain\Entity;

use App\Audit\Infrastructure\Persistence\Doctrine\DoctrineAuditLogRepository;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineAuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(name: 'idx_audit_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_audit_org_created', columns: ['organization_id', 'created_at'])]
#[ORM\Index(name: 'idx_audit_org_entity', columns: ['organization_id', 'entity_type', 'entity_id'])]
#[ORM\Index(name: 'idx_audit_user', columns: ['user_id'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', nullable: false, onDelete: 'RESTRICT', options: ['unsigned' => true])]
    private Organization $organization;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'RESTRICT', options: ['unsigned' => true])]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $action;

    #[ORM\Column(name: 'entity_type', type: Types::STRING, length: 100)]
    private string $entityType;

    #[ORM\Column(name: 'entity_id', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $entityId;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'before_data', type: Types::JSON, nullable: true)]
    private ?array $beforeData;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'after_data', type: Types::JSON, nullable: true)]
    private ?array $afterData;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    private function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    /**
     * @param array<string, mixed>|null $beforeData
     * @param array<string, mixed>|null $afterData
     * @param array<string, mixed>|null $metadata
     */
    public static function record(
        Organization $organization,
        User $user,
        string $action,
        string $entityType,
        int $entityId,
        ?array $beforeData,
        ?array $afterData,
        ?array $metadata,
        \DateTimeImmutable $now,
    ): self {
        $log = new self();
        $log->organization = $organization;
        $log->user = $user;
        $log->action = $action;
        $log->entityType = $entityType;
        $log->entityId = $entityId;
        $log->beforeData = $beforeData;
        $log->afterData = $afterData;
        $log->metadata = $metadata;
        $log->createdAt = $now;

        return $log;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function organization(): Organization
    {
        return $this->organization;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function entityType(): string
    {
        return $this->entityType;
    }

    public function entityId(): int
    {
        return $this->entityId;
    }

    /** @return array<string, mixed>|null */
    public function beforeData(): ?array
    {
        return $this->beforeData;
    }

    /** @return array<string, mixed>|null */
    public function afterData(): ?array
    {
        return $this->afterData;
    }

    /** @return array<string, mixed>|null */
    public function metadata(): ?array
    {
        return $this->metadata;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
