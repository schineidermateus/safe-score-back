<?php

namespace App\Domain\Usuario\Enum;

enum StatusUsuario: string
{
    case ATIVO = 'ATIVO';
    case INATIVO = 'INATIVO';
    case BLOQUEADO = 'BLOQUEADO';
}
