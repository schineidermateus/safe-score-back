<?php

declare(strict_types=1);

namespace App\Receivables\Domain\Repository;

use App\Receivables\Domain\Enum\AgingBucket;
use App\Receivables\Domain\Enum\ReceivableStatus;

final readonly class ReceivableCriteria
{
    public function __construct(
        public ?int $customerId,
        public ?ReceivableStatus $status,
        public ?bool $overdue,
        public ?\DateTimeImmutable $dueDateFrom,
        public ?\DateTimeImmutable $dueDateTo,
        public ?AgingBucket $agingBucket,
        public ?string $amountMin,
        public ?string $amountMax,
        public ?string $search,
        public \DateTimeImmutable $referenceDate,
        public int $page,
        public int $perPage,
        public string $sort,
    ) {
    }
}
