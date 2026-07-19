<?php

declare(strict_types=1);

namespace App\Imports\Domain\Entity;

use App\Imports\Domain\Enum\ImportAction;
use App\Imports\Domain\Enum\ImportRowStatus;
use App\Imports\Infrastructure\Persistence\Doctrine\DoctrineImportRowRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineImportRowRepository::class)]
#[ORM\Table(name: 'import_rows')]
#[ORM\UniqueConstraint(name: 'uniq_import_row_batch_number', columns: ['import_batch_id', 'line_number'])]
#[ORM\Index(name: 'idx_import_row_batch_status', columns: ['import_batch_id', 'status'])]
class ImportRow
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ImportBatch::class)]
    #[ORM\JoinColumn(name: 'import_batch_id', nullable: false, onDelete: 'CASCADE', options: ['unsigned' => true])]
    private ImportBatch $batch;

    #[ORM\Column(name: 'line_number', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $rowNumber;

    /** @var array<string, string|null> */
    #[ORM\Column(name: 'raw_data', type: Types::JSON)]
    private array $rawData;

    /** @var array<string, mixed>|null */
    #[ORM\Column(name: 'normalized_data', type: Types::JSON, nullable: true)]
    private ?array $normalizedData = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: ImportRowStatus::class)]
    private ImportRowStatus $status = ImportRowStatus::Pending;

    /** @var list<array{code: string, field?: string, message: string, severity?: string}> */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $errors = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true, enumType: ImportAction::class)]
    private ?ImportAction $action = null;

    #[ORM\Column(name: 'entity_type', type: Types::STRING, length: 50, nullable: true)]
    private ?string $entityType = null;

    #[ORM\Column(name: 'entity_id', type: Types::BIGINT, nullable: true, options: ['unsigned' => true])]
    private ?int $entityId = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    private function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    /** @param array<string, string|null> $rawData */
    public static function create(ImportBatch $batch, int $rowNumber, array $rawData, \DateTimeImmutable $now): self
    {
        if ($rowNumber < 2) {
            throw new \InvalidArgumentException('CSV data row number must be at least 2.');
        }
        $row = new self();
        $row->batch = $batch;
        $row->rowNumber = $rowNumber;
        $row->rawData = $rawData;
        $row->createdAt = $now;
        $row->updatedAt = $now;

        return $row;
    }

    /**
     * @param array<string, mixed>                                                          $normalizedData
     * @param list<array{code: string, field?: string, message: string, severity?: string}> $warnings
     */
    public function markValid(array $normalizedData, ImportAction $action, \DateTimeImmutable $now, array $warnings = []): void
    {
        $this->normalizedData = $normalizedData;
        $this->action = $action;
        $this->errors = [] === $warnings ? null : array_values($warnings);
        $this->status = ImportAction::Skip === $action ? ImportRowStatus::Valid : ImportRowStatus::Valid;
        $this->updatedAt = $now;
    }

    /**
     * @param list<array{code: string, field?: string, message: string, severity?: string}> $errors
     * @param array<string, mixed>|null                                                     $normalizedData
     */
    public function markInvalid(array $errors, ?array $normalizedData, \DateTimeImmutable $now): void
    {
        $this->normalizedData = $normalizedData;
        $this->errors = $errors;
        $this->action = ImportAction::Error;
        $this->status = ImportRowStatus::Invalid;
        $this->updatedAt = $now;
    }

    public function markProcessed(string $entityType, int $entityId, \DateTimeImmutable $now): void
    {
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->status = ImportRowStatus::Processed;
        $this->updatedAt = $now;
    }

    public function markSkipped(?string $entityType, ?int $entityId, \DateTimeImmutable $now): void
    {
        $this->entityType = $entityType;
        $this->entityId = $entityId;
        $this->status = ImportRowStatus::Skipped;
        $this->updatedAt = $now;
    }

    /** @param array{code: string, field?: string, message: string} $error */
    public function markFailed(array $error, \DateTimeImmutable $now): void
    {
        $this->errors = [$error];
        $this->status = ImportRowStatus::Failed;
        $this->updatedAt = $now;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        return $this->id ?? throw new \LogicException('Import row has not been persisted yet.');
    }

    public function batch(): ImportBatch
    {
        return $this->batch;
    }

    public function rowNumber(): int
    {
        return $this->rowNumber;
    }

    /** @return array<string, string|null> */
    public function rawData(): array
    {
        return $this->rawData;
    }

    /** @return array<string, mixed>|null */
    public function normalizedData(): ?array
    {
        return $this->normalizedData;
    }

    public function status(): ImportRowStatus
    {
        return $this->status;
    }

    /** @return list<array{code: string, field?: string, message: string, severity?: string}> */
    public function errors(): array
    {
        return $this->errors ?? [];
    }

    public function action(): ?ImportAction
    {
        return $this->action;
    }

    public function entityType(): ?string
    {
        return $this->entityType;
    }

    public function entityId(): ?int
    {
        return $this->entityId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
