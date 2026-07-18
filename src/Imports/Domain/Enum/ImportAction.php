<?php

declare(strict_types=1);

namespace App\Imports\Domain\Enum;

enum ImportAction: string
{
    case Create = 'CREATE';
    case Update = 'UPDATE';
    case Skip = 'SKIP';
    case Error = 'ERROR';
}
