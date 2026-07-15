<?php

declare(strict_types=1);

namespace App\Receivables\Application\DTO;

final readonly class ReceivableListOutput
{
    /** @param list<ReceivableOutput> $receivables */
    public function __construct(public array $receivables, public int $page, public int $perPage, public int $total)
    {
    }
}
