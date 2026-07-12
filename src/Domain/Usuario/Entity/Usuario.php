<?php

namespace App\Domain\Usuario\Entity;

use App\Domain\Usuario\Enum\StatusUsuario;
use App\Domain\Usuario\Repository\UsuarioRepository;
use App\Shared\Interface\ResourceInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[ORM\Table(name: 'lm_usuario')]
class Usuario implements PasswordAuthenticatedUserInterface, ResourceInterface, UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 120)]
    private string $nome;

    #[ORM\Column(type: 'string', length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string')]
    private string $senhaHash;

    #[ORM\Column(length: 30, nullable: false, enumType: StatusUsuario::class)]
    private StatusUsuario $status = StatusUsuario::ATIVO;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $dataCadastro;

    public function __construct()
    {
        $this->dataCadastro = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): self
    {
        $this->nome = $nome;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function setSenhaHash(string $senhaHash): self
    {
        $this->senhaHash = $senhaHash;
        return $this;
    }

    public function getStatus(): StatusUsuario
    {
        return $this->status;
    }

    public function setStatus(StatusUsuario $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getDataCadastro(): \DateTimeImmutable
    {
        return $this->dataCadastro;
    }

    public function setDataCadastro(\DateTimeImmutable $dataCadastro): void
    {
        $this->dataCadastro = $dataCadastro;
    }

    public function getPassword(): ?string
    {
        return $this->senhaHash;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function __toArray(): array
    {
        return [
            'id' => $this->getId(),
            'nome' => $this->getNome(),
            'email' => $this->getEmail(),
            'status' => $this->getStatus(),
            'dataCadastro' => $this->getDataCadastro()
        ];
    }
}
