<?php

declare(strict_types=1);

namespace App\Imports\Domain\Enum;

enum ImportType: string
{
    case Customers = 'CUSTOMERS';
    case CreditLimits = 'CREDIT_LIMITS';
    case Receivables = 'RECEIVABLES';
}
