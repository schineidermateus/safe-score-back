<?php

declare(strict_types=1);

namespace App\Identity\Domain\Enum;

enum UserStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Suspended = 'SUSPENDED';
}
