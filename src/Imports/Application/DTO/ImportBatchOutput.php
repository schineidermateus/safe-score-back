<?php

declare(strict_types=1);

namespace App\Imports\Application\DTO;

use App\Imports\Domain\Entity\ImportBatch;

final readonly class ImportBatchOutput
{
    private function __construct(private ImportBatch $batch)
    {
    }

    public static function fromEntity(ImportBatch $batch): self
    {
        return new self($batch);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['id' => $this->batch->requireId(), 'type' => $this->batch->type()->value, 'status' => $this->batch->status()->value, 'file_name' => $this->batch->fileName(), 'original_file_name' => $this->batch->originalFileName(), 'file_hash' => $this->batch->fileHash(), 'file_size' => $this->batch->fileSize(), 'headers' => $this->batch->headers(), 'mapping' => $this->batch->mapping(), 'encoding' => $this->batch->detectedEncoding(), 'delimiter' => $this->batch->detectedDelimiter(), 'total_rows' => $this->batch->totalRows(), 'valid_rows' => $this->batch->validRows(), 'success_rows' => $this->batch->successRows(), 'error_rows' => $this->batch->errorRows(), 'skipped_rows' => $this->batch->skippedRows(), 'failure_code' => $this->batch->failureCode(), 'started_at' => $this->batch->startedAt()?->format(\DATE_ATOM), 'completed_at' => $this->batch->completedAt()?->format(\DATE_ATOM), 'created_at' => $this->batch->createdAt()->format(\DATE_ATOM), 'updated_at' => $this->batch->updatedAt()->format(\DATE_ATOM)];
    }
}
