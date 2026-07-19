<?php

declare(strict_types=1);

namespace App\Industrial\Domain\Enum;

enum BusinessPartnerType: string
{
    case Customer = 'CUSTOMER';
    case Supplier = 'SUPPLIER';
    case ServiceProvider = 'SERVICE_PROVIDER';
    case Quarry = 'QUARRY';
    case Transporter = 'TRANSPORTER';
    case Other = 'OTHER';
}
