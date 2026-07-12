<?php

namespace App\Domain\Conta\Enum;

enum TipoConta: string
{
    case CORRENTE = 'CORRENTE';
    case POUPANCA = 'POUPANCA';
    case INVESTIMENTO = 'INVESTIMENTO';
    case CARTAO_CREDITO = 'CARTAO_CREDITO';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
