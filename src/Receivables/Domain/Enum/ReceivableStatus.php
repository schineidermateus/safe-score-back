<?php

declare(strict_types=1);

namespace App\Receivables\Domain\Enum;

enum ReceivableStatus: string
{
    case Open = 'OPEN';
    case PartiallyPaid = 'PARTIALLY_PAID';
    case Paid = 'PAID';
    case Overdue = 'OVERDUE';
    case Cancelled = 'CANCELLED';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
