<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Model;

use App\Reporting\Domain\Enum\FinancialIndicatorStatus;
use App\Reporting\Domain\ValueObject\DecimalAmount;

final readonly class MoneyResult
{
    private function __construct(public FinancialIndicatorStatus $status, public ?DecimalAmount $value)
    {
    }

    public static function available(DecimalAmount $value): self
    {
        return new self(FinancialIndicatorStatus::Available, $value);
    }

    public static function unavailable(FinancialIndicatorStatus $status): self
    {
        if (FinancialIndicatorStatus::Available === $status) {
            throw new \InvalidArgumentException('An unavailable money result cannot use AVAILABLE status.');
        }

        return new self($status, null);
    }

    /** @return array{status: string, value: string|null} */
    public function toArray(): array
    {
        return ['status' => $this->status->value, 'value' => null === $this->value ? null : (string) $this->value];
    }
}
