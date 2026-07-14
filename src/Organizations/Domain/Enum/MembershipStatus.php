<?php

declare(strict_types=1);

namespace App\Organizations\Domain\Enum;

enum MembershipStatus: string
{
    case Active = 'ACTIVE';
    case Invited = 'INVITED';
    case Suspended = 'SUSPENDED';
    case Removed = 'REMOVED';
}
