<?php

namespace App\Domain\Conta\Service;

use App\Domain\CartaoCredito\Entity\CartaoCredito;

class CartaoCreditoService
{

    public function criarCartao(): CartaoCredito
    {
        return new CartaoCredito();
    }
}
