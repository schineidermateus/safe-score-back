<?php

namespace App\Domain\Transacao\Enum;

enum TipoTransacao: string
{
    case DESPESA = 'DESPESA';
    case RECEITA = 'RECEITA';
    case TRANSFERENCIA = 'TRANSFERENCIA';
}
