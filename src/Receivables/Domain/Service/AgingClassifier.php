<?php

declare(strict_types=1);

namespace App\Receivables\Domain\Service;

use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Enum\AgingBucket;
use App\Receivables\Domain\Enum\ReceivableStatus;

final readonly class AgingClassifier
{
    public function __construct(private ReceivableStatusResolverInterface $statusResolver)
    {
    }

    public function classify(Receivable $receivable, \DateTimeImmutable $referenceDate): ?AgingBucket
    {
        $status = $this->statusResolver->resolve($receivable, $referenceDate);
        if (in_array($status, [ReceivableStatus::Paid, ReceivableStatus::Cancelled], true)) {
            return null;
        }

        $days = (int) $receivable->dueDate()->diff($referenceDate)->format('%r%a');

        return match (true) {
            $days <= 0 => AgingBucket::Upcoming,
            $days <= 15 => AgingBucket::Days1To15,
            $days <= 30 => AgingBucket::Days16To30,
            $days <= 60 => AgingBucket::Days31To60,
            $days <= 90 => AgingBucket::Days61To90,
            default => AgingBucket::Over90,
        };
    }
}
