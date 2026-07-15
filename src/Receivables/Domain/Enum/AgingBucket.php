<?php

declare(strict_types=1);

namespace App\Receivables\Domain\Enum;

enum AgingBucket: string
{
    case Upcoming = 'UPCOMING';
    case Days1To15 = 'DAYS_1_15';
    case Days16To30 = 'DAYS_16_30';
    case Days31To60 = 'DAYS_31_60';
    case Days61To90 = 'DAYS_61_90';
    case Over90 = 'OVER_90';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
