<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Model;

final readonly class DataQualityResult
{
    /** @param list<string> $reasons */
    public function __construct(public int $score, public string $level, public array $reasons)
    {
    }
}
