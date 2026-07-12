<?php

declare(strict_types=1);

namespace App\Customers\Application\DTO;

final readonly class CustomerListOutput
{
    /**
     * @param list<CustomerOutput> $customers
     */
    public function __construct(
        public array $customers,
        public int $page,
        public int $perPage,
        public int $total,
    ) {
    }
}
