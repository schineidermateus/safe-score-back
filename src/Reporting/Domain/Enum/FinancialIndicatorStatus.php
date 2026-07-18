<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Enum;

enum FinancialIndicatorStatus: string
{
    case Available = 'AVAILABLE';
    case NoActiveLimit = 'NO_ACTIVE_LIMIT';
    case NoExposure = 'NO_EXPOSURE';
    case InsufficientHistory = 'INSUFFICIENT_HISTORY';
    case InconsistentData = 'INCONSISTENT_DATA';
    case NoPortfolio = 'NO_PORTFOLIO';
}
