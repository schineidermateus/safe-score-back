<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Model;

use App\Reporting\Domain\Enum\FinancialIndicatorStatus;
use App\Reporting\Domain\ValueObject\DecimalPercentage;

final readonly class PercentageResult
{
    private function __construct(public FinancialIndicatorStatus $status, public ?DecimalPercentage $value)
    {
    }

    public static function available(DecimalPercentage $value): self
    {
        return new self(FinancialIndicatorStatus::Available, $value);
    }

    public static function unavailable(FinancialIndicatorStatus $status): self
    {
        if (FinancialIndicatorStatus::Available === $status) {
            throw new \InvalidArgumentException('An unavailable percentage result cannot use AVAILABLE status.');
        }

        return new self($status, null);
    }

    /** @return array{status: string, value: string|null} */
    public function toArray(): array
    {
        return ['status' => $this->status->value, 'value' => null === $this->value ? null : (string) $this->value];
    }
}
