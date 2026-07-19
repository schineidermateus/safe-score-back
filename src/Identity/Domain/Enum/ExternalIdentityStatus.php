<?php

declare(strict_types=1);

namespace App\Identity\Domain\Enum;

enum ExternalIdentityStatus: string
{
    case Active = 'ACTIVE';
    case Suspended = 'SUSPENDED';
}
