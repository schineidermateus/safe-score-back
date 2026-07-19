<?php

declare(strict_types=1);

namespace App\Industrial\Domain\Enum;

enum FoundationStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
}
