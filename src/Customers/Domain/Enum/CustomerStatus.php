<?php

declare(strict_types=1);

namespace App\Customers\Domain\Enum;

enum CustomerStatus: string
{
    case Active = 'ACTIVE';
    case Inactive = 'INACTIVE';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
