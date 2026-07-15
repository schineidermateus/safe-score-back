<?php

declare(strict_types=1);

namespace App\Shared\Application\Transaction;

interface TransactionManagerInterface
{
    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function transactional(callable $operation): mixed;
}
