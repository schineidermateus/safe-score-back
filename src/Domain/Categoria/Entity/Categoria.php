<?php

namespace App\Domain\Categoria\Entity;

use App\Domain\Categoria\Enum\StatusCategoria;
use App\Domain\Categoria\Enum\TipoCategoria;
use App\Domain\Categoria\Repository\CategoriaRepository;
use App\Domain\Usuario\Entity\Usuario;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CategoriaRepository::class)]
#[ORM\Table(name: "lm_categoria")]
class Categoria
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: "usuario_id", referencedColumnName: "id", nullable: false)]
    private ?Usuario $usuario = null;

    #[ORM\Column(type: "string", length: 100, nullable: false)]
    private string $nome;

    #[ORM\Column(type: "string", nullable: false, enumType: TipoCategoria::class)]
    private TipoCategoria $tipo;

    #[ORM\Column(type: "string", nullable: false, enumType: StatusCategoria::class)]
    private StatusCategoria $status;

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

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }

    public function getTipo(): TipoCategoria
    {
        return $this->tipo;
    }

    public function setTipo(TipoCategoria $tipo): void
    {
        $this->tipo = $tipo;
    }

    public function getStatus(): StatusCategoria
    {
        return $this->status;
    }

    public function setStatus(StatusCategoria $status): void
    {
        $this->status = $status;
    }
}
