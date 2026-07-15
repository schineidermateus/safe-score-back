<?php

declare(strict_types=1);

namespace App\Credit\Application\UseCase;

use App\Credit\Domain\ValueObject\MoneyAmount;
use App\Shared\Domain\Exception\DomainException;

final class CreditLimitInput
{
    public static function amount(string $value): MoneyAmount
    {
        try {
            return new MoneyAmount($value);
        } catch (\InvalidArgumentException) {
            throw new DomainException('CREDIT_LIMIT_INVALID_AMOUNT', 'O valor do limite deve ser um decimal positivo com até duas casas.', 422, 'amount');
        }
    }

    public static function date(?string $value, string $field): \DateTimeImmutable
    {
        if (null === $value) {
            throw new DomainException('CREDIT_LIMIT_INVALID_PERIOD', 'A vigência informada é inválida.', 422, $field);
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (false === $date || $date->format('Y-m-d') !== $value) {
            throw new DomainException('CREDIT_LIMIT_INVALID_PERIOD', 'A vigência informada é inválida.', 422, $field);
        }

        return $date;
    }

    public static function optionalDate(?string $value, string $field): ?\DateTimeImmutable
    {
        return null === $value ? null : self::date($value, $field);
    }

    public static function reason(string $value): string
    {
        $value = trim($value);
        if ('' === $value || mb_strlen($value) > 1000) {
            throw new DomainException('CREDIT_LIMIT_INVALID_REASON', 'A justificativa do limite de crédito é obrigatória e deve possuir até 1000 caracteres.', 422, 'reason');
        }

        return $value;
    }
}
