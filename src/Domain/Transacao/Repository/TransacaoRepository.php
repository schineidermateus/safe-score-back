<?php

namespace App\Domain\Transacao\Repository;

use App\Domain\Conta\Entity\Conta;
use App\Domain\Fatura\Entity\FaturaCartaoCredito;
use App\Domain\Transacao\Entity\Transacao;
use App\Domain\Transacao\Enum\TipoTransacao;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TransacaoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transacao::class);
    }

    /**
     * Retorna transações abertas (não liquidadas) de uma conta específica.
     */
    public function findAbertasPorConta(Conta $conta): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.conta = :conta')
            ->andWhere('t.dataCaixa IS NULL')
            ->andWhere('t.dataVencimento >= :today')
            ->setParameter('conta', $conta)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('t.dataVencimento', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retorna transações vencidas e não liquidadas (atrasadas).
     */
    public function findAtrasadas(): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.dataCaixa IS NULL')
            ->andWhere('t.dataVencimento < :today')
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('t.dataVencimento', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Busca transações por competência (mês/ano).
     */
    public function findPorCompetencia(int $mes, int $ano): array
    {
        $inicio = new \DateTimeImmutable("$ano-$mes-01 00:00:00");
        $fim = $inicio->modify('last day of this month')->setTime(23, 59, 59);

        return $this->createQueryBuilder('t')
            ->andWhere('t.dataCompetencia BETWEEN :inicio AND :fim')
            ->setParameter('inicio', $inicio)
            ->setParameter('fim', $fim)
            ->orderBy('t.dataCompetencia', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcula o saldo consolidado de uma conta.
     * Somente transações liquidadas contam.
     */
    public function calcularSaldoDaConta(Conta $conta): float
    {
        $qb = $this->createQueryBuilder('t')
            ->select("
                SUM(CASE WHEN t.tipoTransacao = :receita THEN t.valorLiquidado ELSE 0 END)
                -
                SUM(CASE WHEN t.tipoTransacao = :despesa THEN t.valorLiquidado ELSE 0 END)
                AS saldo
            ")
            ->andWhere('t.conta = :conta')
            ->andWhere('t.dataCaixa IS NOT NULL') // só liquidadas
            ->setParameter('conta', $conta)
            ->setParameter('receita', TipoTransacao::RECEITA)
            ->setParameter('despesa', TipoTransacao::DESPESA);

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Calcula total, total liquidado e saldo pendente de uma fatura.
     */
    public function calcularSaldoDaFatura(FaturaCartaoCredito $fatura): array
    {
        $qb = $this->createQueryBuilder('t')
            ->select("
                SUM(CASE WHEN t.valor IS NOT NULL THEN t.valor ELSE 0 END) AS totalFatura,
                SUM(CASE WHEN t.valorLiquidado IS NOT NULL THEN t.valorLiquidado ELSE 0 END) AS totalLiquidado
            ")
            ->andWhere('t.fatura = :fatura')
            ->setParameter('fatura', $fatura);

        $result = $qb->getQuery()->getSingleResult();

        return [
            'total' => (float) $result['totalFatura'],
            'liquidado' => (float) $result['totalLiquidado'],
            'pendente' => (float) ($result['totalFatura'] - $result['totalLiquidado']),
        ];
    }
}
