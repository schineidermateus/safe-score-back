<?php

declare(strict_types=1);

namespace App\Credit\Application\DTO;

final readonly class CreditLimitListOutput
{
    /** @param list<CreditLimitOutput> $creditLimits */
    public function __construct(
        public array $creditLimits,
        public int $page,
        public int $perPage,
        public int $total,
    ) {
    }
}
