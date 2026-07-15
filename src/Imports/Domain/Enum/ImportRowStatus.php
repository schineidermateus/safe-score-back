<?php

declare(strict_types=1);

namespace App\Imports\Domain\Enum;

enum ImportRowStatus: string
{
    case Pending = 'PENDING';
    case Valid = 'VALID';
    case Invalid = 'INVALID';
    case Processed = 'PROCESSED';
    case Skipped = 'SKIPPED';
    case Failed = 'FAILED';
}
