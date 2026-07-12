<?php

namespace App\Domain\Conta\Entity;

use App\Domain\CartaoCredito\Entity\CartaoCredito;
use App\Domain\Conta\Enum\StatusConta;
use App\Domain\Conta\Enum\TipoConta;
use App\Domain\Conta\Repository\ContaRepository;
use App\Domain\Usuario\Entity\Usuario;
use App\Shared\Interface\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContaRepository::class)]
#[ORM\Table(name: 'lm_conta')]
class Conta implements ResourceInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class)]
    #[ORM\JoinColumn(name: "usuario_id", referencedColumnName: "id", nullable: false)]
    private Usuario $usuario;

    #[ORM\Column(type: "string", length: 80)]
    private string $nome;

    #[ORM\Column(
        type: "string",
        length: 20,
        enumType: TipoConta::class
    )]
    private TipoConta $tipo;

    #[ORM\Column(
        type: "string",
        length: 20,
        enumType: StatusConta::class
    )]
    private StatusConta $status;

    #[ORM\Column(type: "decimal", precision: 12, scale: 2)]
    private float $saldoInicial;

    #[ORM\OneToOne(
        targetEntity: CartaoCredito::class,
        mappedBy: 'conta',
        cascade: ['persist', 'remove'])
    ]
    private ?CartaoCredito $cartaoCredito = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUsuario(): Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(Usuario $usuario): void
    {
        $this->usuario = $usuario;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }

    public function getTipo(): TipoConta
    {
        return $this->tipo;
    }

    public function setTipo(TipoConta $tipo): void
    {
        $this->tipo = $tipo;
    }

    public function getStatus(): StatusConta
    {
        return $this->status;
    }

    public function setStatus(StatusConta $status): void
    {
        $this->status = $status;
    }

    public function getSaldoInicial(): float
    {
        return $this->saldoInicial;
    }

    public function setSaldoInicial(float $saldoInicial): void
    {
        $this->saldoInicial = $saldoInicial;
    }

    public function getCartaoCredito(): ?CartaoCredito
    {
        return $this->cartaoCredito;
    }

    public function setCartaoCredito(?CartaoCredito $cartaoCredito): void
    {
        $this->cartaoCredito = $cartaoCredito;
    }

    public function __toArray(): array
    {
        return [
            'id' => $this->getId(),
            'usuarioId' => $this->getUsuario()->getId(),
            'nome' => $this->getNome(),
            'tipo' => $this->getTipo(),
            'status' => $this->getStatus(),
            'saldoInicial' => $this->getSaldoInicial(),
            'cartaoCredito' => $this->getCartaoCredito()
        ];
    }
}
