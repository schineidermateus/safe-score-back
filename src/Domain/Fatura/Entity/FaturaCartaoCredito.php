<?php

namespace App\Domain\Fatura\Entity;

use App\Domain\CartaoCredito\Entity\CartaoCredito;
use App\Domain\Fatura\Enum\StatusFaturaCartaoCredito;
use App\Domain\Fatura\Repository\FaturaCartaoCreditoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FaturaCartaoCreditoRepository::class)]
#[ORM\Table(name: 'lm_fatura_cartao_credito')]
class FaturaCartaoCredito
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Fatura pertence a um cartão (N:1)
     */
    #[ORM\ManyToOne(targetEntity: CartaoCredito::class)]
    #[ORM\JoinColumn(name: 'cartao_credito_id', referencedColumnName: 'id', nullable: false)]
    private ?CartaoCredito $cartaoCredito;

    #[ORM\Column(
        type: "string",
        length: 20,
        enumType: StatusFaturaCartaoCredito::class
    )]
    private StatusFaturaCartaoCredito $status;

    #[ORM\Column(name: "data_vencimento", type: "date_immutable")]
    private \DateTimeInterface $dataVencimento;

    /**
     * Soma das transações da fatura
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private float $valorTotal = 0;

    /**
     * Quanto já foi pago
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private float $valorPago = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getCartaoCredito(): ?CartaoCredito
    {
        return $this->cartaoCredito;
    }

    public function setCartaoCredito(?CartaoCredito $cartaoCredito): void
    {
        $this->cartaoCredito = $cartaoCredito;
    }

    public function getStatus(): StatusFaturaCartaoCredito
    {
        return $this->status;
    }

    public function setStatus(StatusFaturaCartaoCredito $status): void
    {
        $this->status = $status;
    }

    public function getDataVencimento(): \DateTimeInterface
    {
        return $this->dataVencimento;
    }

    public function setDataVencimento(\DateTimeInterface $dataVencimento): void
    {
        $this->dataVencimento = $dataVencimento;
    }

    public function getValorTotal(): float
    {
        return $this->valorTotal;
    }

    public function setValorTotal(float $valorTotal): void
    {
        $this->valorTotal = $valorTotal;
    }

    public function getValorPago(): float
    {
        return $this->valorPago;
    }

    public function setValorPago(float $valorPago): void
    {
        $this->valorPago = $valorPago;
    }
}
