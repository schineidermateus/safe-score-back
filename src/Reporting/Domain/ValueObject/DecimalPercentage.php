<?php

declare(strict_types=1);

namespace App\Reporting\Domain\ValueObject;

final readonly class DecimalPercentage implements \Stringable
{
    /** @var numeric-string */
    private string $value;

    /** @param numeric-string $value */
    private function __construct(string $value)
    {
        if (bccomp($value, '0', 6) < 0) {
            throw new \InvalidArgumentException('Percentage cannot be negative.');
        }

        $this->value = bcround($value, 2, \RoundingMode::HalfAwayFromZero);
    }

    public static function ratio(DecimalAmount $part, DecimalAmount $total): self
    {
        if (!$total->isPositive()) {
            throw new \InvalidArgumentException('Percentage denominator must be positive.');
        }

        return new self(bcmul(bcdiv((string) $part, (string) $total, 6), '100', 6));
    }

    public static function fromCounts(int $part, int $total): self
    {
        if ($part < 0 || $total <= 0 || $part > $total) {
            throw new \InvalidArgumentException('Percentage counts are inconsistent.');
        }

        return new self(bcmul(bcdiv((string) $part, (string) $total, 6), '100', 6));
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
