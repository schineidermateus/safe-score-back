<?php

namespace App\Domain\Fatura\Enum;

enum StatusFaturaCartaoCredito: string
{
    case ABERTA = 'ABERTA';
    case FECHADA = 'FECHADA';
}
