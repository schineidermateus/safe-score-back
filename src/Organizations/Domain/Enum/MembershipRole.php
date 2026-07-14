<?php

declare(strict_types=1);

namespace App\Organizations\Domain\Enum;

enum MembershipRole: string
{
    case Owner = 'OWNER';
    case Admin = 'ADMIN';
    case Analyst = 'ANALYST';
    case Viewer = 'VIEWER';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
