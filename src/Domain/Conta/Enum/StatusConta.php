<?php

namespace App\Domain\Conta\Enum;

enum StatusConta: string
{
    case ATIVA = 'ATIVA';
    case INATIVA = 'INATIVA';
    case BLOQUEADA = 'BLOQUEADA';
}
