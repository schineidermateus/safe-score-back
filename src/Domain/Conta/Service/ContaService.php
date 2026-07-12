<?php

namespace App\Domain\Conta\Service;

use App\Domain\Conta\Entity\Conta;
use App\Domain\Conta\Enum\StatusConta;
use App\Domain\Conta\Enum\TipoConta;
use App\Domain\Conta\Repository\ContaRepository;
use App\Infrastructure\Security\LoggedInUserTrait;

class ContaService
{
    use LoggedInUserTrait;

    public function __construct(
        private readonly ContaRepository $contaRepository,
    ){
    }

    public function criarConta(string $nome, TipoConta $tipoConta, float $saldoInicial = 0): Conta
    {
        $conta = new Conta();

        $conta->setNome($nome);
        $conta->setUsuario($this->requireLoggedInUser());
        $conta->setTipo($tipoConta);
        $conta->setSaldoInicial($saldoInicial);
        $conta->setStatus(StatusConta::ATIVA);

        $this->contaRepository->save($conta);

        return $conta;
    }
}
