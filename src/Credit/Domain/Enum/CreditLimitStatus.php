<?php

declare(strict_types=1);

namespace App\Credit\Domain\Enum;

enum CreditLimitStatus: string
{
    case Draft = 'DRAFT';
    case Active = 'ACTIVE';
    case Expired = 'EXPIRED';
    case Revoked = 'REVOKED';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
