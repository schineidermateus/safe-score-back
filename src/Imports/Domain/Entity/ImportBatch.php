<?php

declare(strict_types=1);

namespace App\Imports\Domain\Entity;

use App\Identity\Domain\Entity\User;
use App\Imports\Domain\Enum\ImportBatchStatus;
use App\Imports\Domain\Enum\ImportType;
use App\Imports\Infrastructure\Persistence\Doctrine\DoctrineImportBatchRepository;
use App\Organizations\Domain\Entity\Organization;
use App\Shared\Domain\Exception\DomainException;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineImportBatchRepository::class)]
#[ORM\Table(name: 'import_batches')]
#[ORM\Index(name: 'idx_import_batch_org_status', columns: ['organization_id', 'status'])]
#[ORM\Index(name: 'idx_import_batch_org_type_created', columns: ['organization_id', 'type', 'created_at'])]
#[ORM\Index(name: 'idx_import_batch_org_hash_type', columns: ['organization_id', 'file_hash', 'type'])]
class ImportBatch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', nullable: false, onDelete: 'RESTRICT', options: ['unsigned' => true])]
    private Organization $organization;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_user_id', nullable: false, onDelete: 'RESTRICT', options: ['unsigned' => true])]
    private User $createdBy;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: ImportType::class)]
    private ImportType $type;

    #[ORM\Column(name: 'file_name', type: Types::STRING, length: 255)]
    private string $fileName;

    #[ORM\Column(name: 'original_file_name', type: Types::STRING, length: 255)]
    private string $originalFileName;

    #[ORM\Column(name: 'storage_key', type: Types::STRING, length: 255, unique: true)]
    private string $storageKey;

    #[ORM\Column(name: 'file_hash', type: Types::STRING, length: 64)]
    private string $fileHash;

    #[ORM\Column(name: 'file_size', type: Types::INTEGER, options: ['unsigned' => true])]
    private int $fileSize;

    /** @var array<string, string>|null */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $mapping = null;

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    private array $headers = [];

    #[ORM\Column(name: 'detected_encoding', type: Types::STRING, length: 30)]
    private string $detectedEncoding;

    #[ORM\Column(name: 'detected_delimiter', type: Types::STRING, length: 1)]
    private string $detectedDelimiter;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: ImportBatchStatus::class)]
    private ImportBatchStatus $status;

    #[ORM\Column(name: 'total_rows', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $totalRows = 0;

    #[ORM\Column(name: 'valid_rows', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $validRows = 0;

    #[ORM\Column(name: 'success_rows', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $successRows = 0;

    #[ORM\Column(name: 'error_rows', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $errorRows = 0;

    #[ORM\Column(name: 'skipped_rows', type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    private int $skippedRows = 0;

    #[ORM\Column(name: 'failure_code', type: Types::STRING, length: 100, nullable: true)]
    private ?string $failureCode = null;

    #[ORM\Column(name: 'started_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(name: 'completed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    private function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    /** @param list<string> $headers */
    public static function create(Organization $organization, User $user, ImportType $type, string $fileName, string $originalFileName, string $storageKey, string $fileHash, int $fileSize, array $headers, string $encoding, string $delimiter, \DateTimeImmutable $now): self
    {
        $batch = new self();
        $batch->organization = $organization;
        $batch->createdBy = $user;
        $batch->type = $type;
        $batch->fileName = $fileName;
        $batch->originalFileName = $originalFileName;
        $batch->storageKey = $storageKey;
        $batch->fileHash = $fileHash;
        $batch->fileSize = $fileSize;
        $batch->headers = $headers;
        $batch->detectedEncoding = $encoding;
        $batch->detectedDelimiter = $delimiter;
        $batch->status = ImportBatchStatus::MappingRequired;
        $batch->createdAt = $now;
        $batch->updatedAt = $now;

        return $batch;
    }

    /** @param array<string, string> $mapping */
    public function setMapping(array $mapping, \DateTimeImmutable $now): void
    {
        if (!in_array($this->status, [ImportBatchStatus::MappingRequired, ImportBatchStatus::Ready], true)) {
            $this->invalidTransition('alterar o mapeamento');
        }
        $this->mapping = $mapping;
        $this->status = ImportBatchStatus::MappingRequired;
        $this->resetCounters();
        $this->updatedAt = $now;
    }

    public function startValidation(\DateTimeImmutable $now): void
    {
        if (ImportBatchStatus::MappingRequired !== $this->status || null === $this->mapping) {
            $this->invalidTransition('validar');
        }
        $this->status = ImportBatchStatus::Validating;
        $this->resetCounters();
        $this->updatedAt = $now;
    }

    public function finishValidation(int $total, int $valid, int $invalid, \DateTimeImmutable $now): void
    {
        if (ImportBatchStatus::Validating !== $this->status) {
            $this->invalidTransition('finalizar a validação');
        }
        $this->totalRows = $total;
        $this->validRows = $valid;
        $this->errorRows = $invalid;
        $this->status = ImportBatchStatus::Ready;
        $this->updatedAt = $now;
    }

    public function startProcessing(\DateTimeImmutable $now): void
    {
        if (ImportBatchStatus::Ready !== $this->status) {
            $this->invalidTransition('processar');
        }
        $this->status = ImportBatchStatus::Processing;
        $this->startedAt = $now;
        $this->successRows = 0;
        $this->skippedRows = 0;
        $this->updatedAt = $now;
    }

    public function finishProcessing(int $success, int $skipped, int $processingErrors, \DateTimeImmutable $now): void
    {
        if (ImportBatchStatus::Processing !== $this->status) {
            $this->invalidTransition('finalizar o processamento');
        }
        $this->successRows = $success;
        $this->skippedRows = $skipped;
        $this->errorRows += $processingErrors;
        $this->status = $this->errorRows > 0 ? ImportBatchStatus::CompletedWithErrors : ImportBatchStatus::Completed;
        $this->completedAt = $now;
        $this->updatedAt = $now;
    }

    public function fail(string $safeCode, \DateTimeImmutable $now): void
    {
        if ($this->status->terminal()) {
            $this->invalidTransition('marcar como falho');
        }
        $this->failureCode = $safeCode;
        $this->status = ImportBatchStatus::Failed;
        $this->completedAt = $now;
        $this->updatedAt = $now;
    }

    public function failProcessing(int $success, int $skipped, int $processingErrors, string $safeCode, \DateTimeImmutable $now): void
    {
        if (ImportBatchStatus::Processing !== $this->status) {
            $this->invalidTransition('registrar falha de processamento');
        }
        $this->successRows = $success;
        $this->skippedRows = $skipped;
        $this->errorRows += $processingErrors;
        $this->failureCode = $safeCode;
        $this->status = ImportBatchStatus::Failed;
        $this->completedAt = $now;
        $this->updatedAt = $now;
    }

    public function cancel(\DateTimeImmutable $now): void
    {
        if (!in_array($this->status, [ImportBatchStatus::MappingRequired, ImportBatchStatus::Ready], true)) {
            $this->invalidTransition('cancelar');
        }
        $this->status = ImportBatchStatus::Cancelled;
        $this->completedAt = $now;
        $this->updatedAt = $now;
    }

    public function assertPreviewAvailable(): void
    {
        if (!in_array($this->status, [ImportBatchStatus::Ready, ImportBatchStatus::Processing, ImportBatchStatus::Completed, ImportBatchStatus::CompletedWithErrors, ImportBatchStatus::Cancelled], true) || 0 === $this->totalRows) {
            $this->invalidTransition('consultar o preview');
        }
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        return $this->id ?? throw new \LogicException('Import batch has not been persisted yet.');
    }

    public function organization(): Organization
    {
        return $this->organization;
    }

    public function createdBy(): User
    {
        return $this->createdBy;
    }

    public function type(): ImportType
    {
        return $this->type;
    }

    public function fileName(): string
    {
        return $this->fileName;
    }

    public function originalFileName(): string
    {
        return $this->originalFileName;
    }

    public function storageKey(): string
    {
        return $this->storageKey;
    }

    public function fileHash(): string
    {
        return $this->fileHash;
    }

    public function fileSize(): int
    {
        return $this->fileSize;
    }

    /** @return array<string, string>|null */
    public function mapping(): ?array
    {
        return $this->mapping;
    }

    /** @return list<string> */
    public function headers(): array
    {
        return $this->headers;
    }

    public function detectedEncoding(): string
    {
        return $this->detectedEncoding;
    }

    public function detectedDelimiter(): string
    {
        return $this->detectedDelimiter;
    }

    public function status(): ImportBatchStatus
    {
        return $this->status;
    }

    public function totalRows(): int
    {
        return $this->totalRows;
    }

    public function validRows(): int
    {
        return $this->validRows;
    }

    public function successRows(): int
    {
        return $this->successRows;
    }

    public function errorRows(): int
    {
        return $this->errorRows;
    }

    public function skippedRows(): int
    {
        return $this->skippedRows;
    }

    public function failureCode(): ?string
    {
        return $this->failureCode;
    }

    public function startedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function completedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function resetCounters(): void
    {
        $this->totalRows = $this->validRows = $this->successRows = $this->errorRows = $this->skippedRows = 0;
        $this->failureCode = null;
    }

    private function invalidTransition(string $operation): never
    {
        throw new DomainException('IMPORT_INVALID_STATUS_TRANSITION', sprintf('O lote no estado %s não pode %s.', $this->status->value, $operation), 409);
    }
}
