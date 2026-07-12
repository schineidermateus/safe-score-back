<?php

namespace App\Domain\Categoria\Enum;

enum TipoCategoria: string
{
    case RECEITA = 'RECEITA';
    case DESPESA = 'DESPESA';
    case TRANSFERENCIA = 'TRANSFERENCIA';
}
