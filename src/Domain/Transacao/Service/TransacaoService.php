<?php

namespace App\Domain\Transacao\Service;

use App\Domain\Conta\Entity\Conta;
use App\Domain\Conta\Repository\ContaRepository;
use App\Domain\Fatura\Entity\FaturaCartaoCredito;
use App\Domain\Fatura\Repository\FaturaCartaoCreditoRepository;
use App\Domain\Transacao\Entity\Transacao;
use App\Domain\Transacao\Repository\TransacaoRepository;
use Doctrine\ORM\EntityManagerInterface;

class TransacaoService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly TransacaoRepository $transacaoRepository,
        private readonly ContaRepository $contaRepository,
        private readonly FaturaCartaoCreditoRepository $faturaRepository,
    ) {}

    public function criar(Transacao $transacao): Transacao
    {
        $this->validarTransacao($transacao);

        $this->em->persist($transacao);

        // Se for transferência, também persistimos a contraparte
        if ($transacao->getTransferencia()) {
            $this->em->persist($transacao->getTransferencia());
        }

        $this->em->flush();

        return $transacao;
    }

    public function liquidar(Transacao $transacao, float $valor, \DateTimeInterface $dataCaixa): Transacao
    {
        if ($valor <= 0) {
            throw new \InvalidArgumentException("O valor liquidado deve ser maior que zero.");
        }

        if ($valor > $transacao->getValor()) {
            throw new \InvalidArgumentException("Valor liquidado não pode exceder o valor original.");
        }

        $transacao->setValorLiquidado($valor);
        $transacao->setDataCaixa($dataCaixa);

        $this->em->flush();

        return $transacao;
    }

    public function criarTransferencia(
        Conta $origem,
        Conta $destino,
        float $valor,
        \DateTimeInterface $vencimento,
        string $descricao = ''
    ): array {

        if ($valor <= 0) {
            throw new \InvalidArgumentException("O valor da transferência deve ser maior que zero.");
        }

        $saida = new Transacao();
        $saida->setConta($origem);
        $saida->setTipoTransacao('DESPESA');
        $saida->setValor($valor);
        $saida->setDataVencimento($vencimento);
        $saida->setDescricao($descricao);

        $entrada = new Transacao();
        $entrada->setConta($destino);
        $entrada->setTipoTransacao('RECEITA');
        $entrada->setValor($valor);
        $entrada->setDataVencimento($vencimento);
        $entrada->setDescricao($descricao);

        $saida->setTransferencia($entrada);
        $entrada->setTransferencia($saida);

        $this->em->persist($saida);
        $this->em->persist($entrada);
        $this->em->flush();

        return [$saida, $entrada];
    }

    public function associarAFatura(Transacao $transacao, FaturaCartaoCredito $fatura): Transacao
    {
        if ($transacao->getConta()->getId() !== $fatura->getCartaoCredito()->getConta()->getId()) {
            throw new \LogicException("Transação não pertence ao mesmo cartão da fatura.");
        }

        $transacao->setFatura($fatura);

        $this->em->flush();

        return $transacao;
    }

    public function buscarAbertasPorConta(Conta $conta): array
    {
        return $this->transacaoRepository->findAbertasPorConta($conta);
    }

    public function buscarAtrasadas(): array
    {
        return $this->transacaoRepository->findAtrasadas();
    }

    public function buscarPorCompetencia(\DateTimeInterface $mes): array
    {
        return $this->transacaoRepository->findPorCompetencia($mes);
    }

//    public function calcularSaldoDaConta(Conta $conta): float
//    {
//        return $this->transacaoRepository->calcularSaldoDaConta($conta);
//    }
//
//    public function calcularSaldoDaFatura(Fatura $fatura): float
//    {
//        return $this->transacaoRepository->calcularSaldoDaFatura($fatura);
//    }

    private function validarTransacao(Transacao $t)
    {
        if ($t->getValor() <= 0) {
            throw new \InvalidArgumentException("Valor da transação deve ser maior que zero.");
        }

        if (!$t->getConta()) {
            throw new \InvalidArgumentException("Transação precisa estar vinculada a uma conta.");
        }

        if (!$t->getTipoTransacao()) {
            throw new \InvalidArgumentException("Tipo da transação é obrigatório.");
        }
    }
}
