<?php

declare(strict_types=1);

namespace App\Imports\Domain\Enum;

enum ImportBatchStatus: string
{
    case MappingRequired = 'MAPPING_REQUIRED';
    case Validating = 'VALIDATING';
    case Ready = 'READY';
    case Processing = 'PROCESSING';
    case Completed = 'COMPLETED';
    case CompletedWithErrors = 'COMPLETED_WITH_ERRORS';
    case Failed = 'FAILED';
    case Cancelled = 'CANCELLED';

    public function terminal(): bool
    {
        return in_array($this, [self::Completed, self::CompletedWithErrors, self::Failed, self::Cancelled], true);
    }
}
