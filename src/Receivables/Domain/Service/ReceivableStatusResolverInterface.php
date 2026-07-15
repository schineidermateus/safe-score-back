<?php

declare(strict_types=1);

namespace App\Receivables\Domain\Service;

use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Enum\ReceivableStatus;

interface ReceivableStatusResolverInterface
{
    public function resolve(Receivable $receivable, \DateTimeImmutable $referenceDate): ReceivableStatus;
}
