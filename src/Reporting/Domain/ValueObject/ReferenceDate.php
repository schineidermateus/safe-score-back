<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ValueObject;

final readonly class ReferenceDate implements \Stringable
{
    private \DateTimeImmutable $value;

    private function __construct(\DateTimeImmutable $value)
    {
        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value, new \DateTimeZone('UTC'));
        if (false === $date || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException('Reference date must use the Y-m-d format.');
        }

        return new self($date);
    }

    public function toDateTimeImmutable(): \DateTimeImmutable
    {
        return $this->value;
    }

    public function overdueDaysSince(\DateTimeImmutable $dueDate): int
    {
        return max(0, (int) $dueDate->diff($this->value)->format('%r%a'));
    }

    public function __toString(): string
    {
        return $this->value->format('Y-m-d');
    }
}
