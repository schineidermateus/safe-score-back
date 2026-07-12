<?php

namespace App\Domain\CartaoCredito\Entity;

use App\Domain\Conta\Entity\Conta;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'lm_cartao_credito')]
class CartaoCredito
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $plastico = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $limite;

    #[ORM\Column]
    private int $diaFechamento;

    #[ORM\Column]
    private int $diaVencimento;

    #[ORM\OneToOne(inversedBy: 'cartaoCredito')]
    #[ORM\JoinColumn(name: "conta_id", referencedColumnName: "id", nullable: false)]
    private ?Conta $conta = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getPlastico(): ?string
    {
        return $this->plastico;
    }

    public function setPlastico(?string $plastico): void
    {
        $this->plastico = $plastico;
    }

    public function getLimite(): string
    {
        return $this->limite;
    }

    public function setLimite(string $limite): void
    {
        $this->limite = $limite;
    }

    public function getDiaFechamento(): int
    {
        return $this->diaFechamento;
    }

    public function setDiaFechamento(int $diaFechamento): void
    {
        $this->diaFechamento = $diaFechamento;
    }

    public function getDiaVencimento(): int
    {
        return $this->diaVencimento;
    }

    public function setDiaVencimento(int $diaVencimento): void
    {
        $this->diaVencimento = $diaVencimento;
    }

    public function getConta(): ?Conta
    {
        return $this->conta;
    }

    public function setConta(?Conta $conta): void
    {
        $this->conta = $conta;
    }
}
