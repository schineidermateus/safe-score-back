<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Shared\Application\Transaction\TransactionManagerInterface;

final class ImmediateTransactionManager implements TransactionManagerInterface
{
    /**
     * @template T
     *
     * @param callable(): T $operation
     *
     * @return T
     */
    public function transactional(callable $operation): mixed
    {
        return $operation();
    }
}
