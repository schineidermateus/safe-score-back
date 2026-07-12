<?php

namespace App\Domain\Transacao\Entity;

use App\Domain\Categoria\Entity\Categoria;
use App\Domain\Conta\Entity\Conta;
use App\Domain\Fatura\Entity\FaturaCartaoCredito;
use App\Domain\Transacao\Enum\TipoTransacao;
use App\Domain\Transacao\Repository\TransacaoRepository;
use App\Domain\Usuario\Entity\Usuario;
use App\Shared\Interface\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransacaoRepository::class)]
#[ORM\Table(name: "lm_transacao")]
#[ORM\Index(name: "idx_vencimento", columns: ["data_vencimento"])]
#[ORM\Index(name: "idx_caixa", columns: ["data_caixa"])]
#[ORM\Index(name: "idx_competencia", columns: ["data_competencia"])]
class Transacao implements ResourceInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "usuario_id", referencedColumnName: "id", nullable: false)]
    private ?Usuario $usuario = null;

    #[ORM\Column(name: "tipo_transacao", type: "string", enumType: TipoTransacao::class)]
    private TipoTransacao $tipoTransacao;

    #[ORM\Column(type: "decimal", precision: 15, scale: 2)]
    private float $valor;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "conta_id", referencedColumnName: "id", nullable: false)]
    private Conta $conta;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "categoria_id", referencedColumnName: "id", nullable: false)]
    private Categoria $categoria;

    #[ORM\Column(name: "data_criacao", type: "datetime_immutable")]
    private \DateTimeImmutable $dataCriacao;

    #[ORM\Column(name: "data_vencimento", type: "date_immutable")]
    private \DateTimeInterface $dataVencimento;

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $descricao = null;

    #[ORM\Column(
        name: "valor_liquidado",
        type: "decimal",
        precision: 15,
        scale: 2,
        nullable: true
    )]
    private ?float $valorLiquidado = null;

    // TRANSFERÊNCIA (auto-relacionamento)
    #[ORM\OneToOne]
    #[ORM\JoinColumn(name: "transferencia_id", referencedColumnName: "id", nullable: true)]
    private ?Transacao $transferencia = null;

    // FATURA (muitas transações para 1 fatura)
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "fatura_id", referencedColumnName: "id", nullable: true)]
    private ?FaturaCartaoCredito $fatura = null;

    // Datas contábeis opcionais
    #[ORM\Column(name: "data_caixa", type: "date_immutable", nullable: true)]
    private ?\DateTimeInterface $dataCaixa = null;

    #[ORM\Column(name: "data_competencia", type: "date_immutable", nullable: true)]
    private ?\DateTimeInterface $dataCompetencia = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUsuario(): ?Usuario
    {
        return $this->usuario;
    }

    public function setUsuario(?Usuario $usuario): void
    {
        $this->usuario = $usuario;
    }

    public function getTipoTransacao(): TipoTransacao
    {
        return $this->tipoTransacao;
    }

    public function setTipoTransacao(TipoTransacao $tipoTransacao): void
    {
        $this->tipoTransacao = $tipoTransacao;
    }

    public function getValor(): float
    {
        return $this->valor;
    }

    public function setValor(float $valor): void
    {
        $this->valor = $valor;
    }

    public function getConta(): Conta
    {
        return $this->conta;
    }

    public function setConta(Conta $conta): void
    {
        $this->conta = $conta;
    }

    public function getCategoria(): Categoria
    {
        return $this->categoria;
    }

    public function setCategoria(Categoria $categoria): void
    {
        $this->categoria = $categoria;
    }

    public function getDataCriacao(): \DateTimeImmutable
    {
        return $this->dataCriacao;
    }

    public function setDataCriacao(\DateTimeImmutable $dataCriacao): void
    {
        $this->dataCriacao = $dataCriacao;
    }

    public function getDataVencimento(): \DateTimeInterface
    {
        return $this->dataVencimento;
    }

    public function setDataVencimento(\DateTimeInterface $dataVencimento): void
    {
        $this->dataVencimento = $dataVencimento;
    }

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function setDescricao(?string $descricao): void
    {
        $this->descricao = $descricao;
    }

    public function getValorLiquidado(): ?float
    {
        return $this->valorLiquidado;
    }

    public function setValorLiquidado(?float $valorLiquidado): void
    {
        $this->valorLiquidado = $valorLiquidado;
    }

    public function getTransferencia(): ?Transacao
    {
        return $this->transferencia;
    }

    public function setTransferencia(?Transacao $transferencia): void
    {
        $this->transferencia = $transferencia;
    }

    public function getFatura(): ?FaturaCartaoCredito
    {
        return $this->fatura;
    }

    public function setFatura(?FaturaCartaoCredito $fatura): void
    {
        $this->fatura = $fatura;
    }

    public function getDataCaixa(): ?\DateTimeInterface
    {
        return $this->dataCaixa;
    }

    public function setDataCaixa(?\DateTimeInterface $dataCaixa): void
    {
        $this->dataCaixa = $dataCaixa;
    }

    public function getDataCompetencia(): ?\DateTimeInterface
    {
        return $this->dataCompetencia;
    }

    public function setDataCompetencia(?\DateTimeInterface $dataCompetencia): void
    {
        $this->dataCompetencia = $dataCompetencia;
    }

    public function __toArray(): array
    {
        return [
            'id' => $this->getId(),
            'usuario' => $this->getUsuario()->getId(),
            'tipoTransacao' => $this->getTipoTransacao(),
            'valor' => $this->getValor(),
            'conta' => $this->getConta()->getId(),
            'categoria' => $this->getCategoria()->getId(),
            'dataCriacao' => $this->getDataCriacao(),
            'dataVencimento' => $this->getDataVencimento(),
            'descricao' => $this->getDescricao(),
            'valorLiquidado' => $this->getValorLiquidado(),
            'transferencia' => $this->getTransferencia(),
            'fatura' => $this->getFatura(),
            'dataCaixa' => $this->getDataCaixa(),
            'dataCompetencia' => $this->getDataCompetencia(),
        ];
    }

}
