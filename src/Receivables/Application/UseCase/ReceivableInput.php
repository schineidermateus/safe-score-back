<?php

declare(strict_types=1);

namespace App\Receivables\Application\UseCase;

use App\Receivables\Domain\ValueObject\ReceivableAmount;
use App\Shared\Domain\Exception\DomainException;

final class ReceivableInput
{
    public static function amount(string $value, string $field = 'amount'): ReceivableAmount
    {
        try {
            return new ReceivableAmount($value);
        } catch (\InvalidArgumentException) {
            throw new DomainException('RECEIVABLE_INVALID_AMOUNT', 'O valor deve ser decimal não negativo com até duas casas.', 422, $field);
        }
    }

    public static function date(?string $value, string $field): \DateTimeImmutable
    {
        $date = null === $value ? false : \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (false === $date || $date->format('Y-m-d') !== $value) {
            throw new DomainException('RECEIVABLE_INVALID_DATES', 'A data informada é inválida.', 422, $field);
        }

        return $date;
    }

    public static function referenceDate(?string $value): \DateTimeImmutable
    {
        return null === $value ? new \DateTimeImmutable('today') : self::date($value, 'reference_date');
    }

    public static function assertValidPeriod(\DateTimeImmutable $issueDate, \DateTimeImmutable $dueDate): void
    {
        if ($dueDate < $issueDate) {
            throw new DomainException('RECEIVABLE_INVALID_DATES', 'A data de vencimento não pode ser anterior à emissão.', 422, 'due_date');
        }
    }
}
