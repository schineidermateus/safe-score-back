<?php

declare(strict_types=1);

namespace App\Organizations\Domain\Enum;

enum OrganizationStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';
    case Suspended = 'SUSPENDED';
}
