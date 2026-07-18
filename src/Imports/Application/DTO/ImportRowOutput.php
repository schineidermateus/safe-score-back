<?php

declare(strict_types=1);

namespace App\Imports\Application\DTO;

use App\Imports\Domain\Entity\ImportRow;

final readonly class ImportRowOutput
{
    private function __construct(private ImportRow $row)
    {
    }

    public static function fromEntity(ImportRow $row): self
    {
        return new self($row);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['id' => $this->row->requireId(), 'row_number' => $this->row->rowNumber(), 'raw_data' => $this->row->rawData(), 'normalized_data' => $this->row->normalizedData(), 'status' => $this->row->status()->value, 'action' => $this->row->action()?->value, 'errors' => $this->row->errors(), 'entity_type' => $this->row->entityType(), 'entity_id' => $this->row->entityId()];
    }
}
