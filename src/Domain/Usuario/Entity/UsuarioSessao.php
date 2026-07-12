<?php

namespace App\Domain\Usuario\Entity;

use App\Domain\Usuario\Repository\UsuarioSessaoRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UsuarioSessaoRepository::class)]
#[ORM\Table(name: "lm_usuario_sessao")]
#[ORM\Index(name: "idx_refresh_token", columns: ["refresh_token"])]
#[ORM\Index(name: "idx_usuario_id", columns: ["usuario_id"])]
class UsuarioSessao
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[ORM\Column(type: "bigint")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Usuario::class, inversedBy: "sessoes")]
    #[ORM\JoinColumn(name: "usuario_id", referencedColumnName: "id", nullable: false, onDelete: "CASCADE")]
    private Usuario $usuario;

    #[ORM\Column(name: "refresh_token", type: "string", length: 255, unique: true)]
    private string $refreshToken;

    #[ORM\Column(name: "ip_address", type: "string", length: 45, nullable: true)]
    private ?string $ipAddress;

    #[ORM\Column(name: "user_agent", type: "string", length: 500, nullable: true)]
    private ?string $userAgent;

    #[ORM\Column(name: "revogado", type: "boolean")]
    private bool $revogado = false;

    #[ORM\Column(name: "data_criacao", type: "datetime_immutable")]
    private \DateTimeImmutable $dataCriacao;

    #[ORM\Column(name: "data_expiracao", type: "datetime_immutable")]
    private \DateTimeImmutable $dataExpiracao;

    #[ORM\Column(name: "ultimo_acesso", type: "datetime_immutable", nullable: true)]
    private ?\DateTimeImmutable $ultimoAcesso = null;

    public function __construct(
        Usuario $usuario,
        string $refreshToken,
        \DateTimeImmutable $expiraEm,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ) {
        $this->usuario = $usuario;
        $this->refreshToken = $refreshToken;
        $this->dataExpiracao = $expiraEm;
        $this->ipAddress = $ipAddress;
        $this->userAgent = $userAgent;
        $this->dataCriacao = new \DateTimeImmutable();
    }

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

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(?string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function isRevogado(): bool
    {
        return $this->revogado;
    }

    public function setRevogado(bool $revogado): void
    {
        $this->revogado = $revogado;
    }

    public function getDataCriacao(): \DateTimeImmutable
    {
        return $this->dataCriacao;
    }

    public function setDataCriacao(\DateTimeImmutable $dataCriacao): void
    {
        $this->dataCriacao = $dataCriacao;
    }

    public function getDataExpiracao(): \DateTimeImmutable
    {
        return $this->dataExpiracao;
    }

    public function setDataExpiracao(\DateTimeImmutable $dataExpiracao): void
    {
        $this->dataExpiracao = $dataExpiracao;
    }

    public function getUltimoAcesso(): ?\DateTimeImmutable
    {
        return $this->ultimoAcesso;
    }

    public function setUltimoAcesso(?\DateTimeImmutable $ultimoAcesso): void
    {
        $this->ultimoAcesso = $ultimoAcesso;
    }

    /**
     * Marca a sessão como revogada (logout).
     */
    public function revogar(): void
    {
        $this->revogado = true;
    }

    /**
     * Verifica se a sessão já expirou.
     */
    public function estaExpirado(): bool
    {
        return $this->dataExpiracao <= new \DateTimeImmutable();
    }

    /**
     * Atualiza o token e a data de expiração — usado para refresh token.
     */
    public function renovar(string $novoToken, \DateTimeImmutable $novaExpiracao): void
    {
        $this->token = $novoToken;
        $this->dataExpiracao = $novaExpiracao;
        $this->revogado = false;
    }
}
