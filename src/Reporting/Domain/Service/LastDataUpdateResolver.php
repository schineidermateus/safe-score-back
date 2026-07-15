<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Service;

final readonly class LastDataUpdateResolver
{
    public function resolve(?\DateTimeImmutable ...$timestamps): ?\DateTimeImmutable
    {
        $timestamps = array_values(array_filter($timestamps));
        if ([] === $timestamps) {
            return null;
        }

        usort($timestamps, static fn (\DateTimeImmutable $a, \DateTimeImmutable $b): int => $b->getTimestamp() <=> $a->getTimestamp());

        return $timestamps[0];
    }
}
