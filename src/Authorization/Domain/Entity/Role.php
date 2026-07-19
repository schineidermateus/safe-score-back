<?php

declare(strict_types=1);

namespace App\Authorization\Domain\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'roles')]
#[ORM\UniqueConstraint(name: 'uniq_role_code', columns: ['code'])]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 80)]
    private string $code;

    #[ORM\Column(type: Types::STRING, length: 160)]
    private string $name;

    /** @var Collection<int, Capability> */
    #[ORM\ManyToMany(targetEntity: Capability::class)]
    #[ORM\JoinTable(name: 'role_capabilities')]
    #[ORM\JoinColumn(name: 'role_id', referencedColumnName: 'id', onDelete: 'CASCADE', options: ['unsigned' => true])]
    #[ORM\InverseJoinColumn(name: 'capability_id', referencedColumnName: 'id', onDelete: 'CASCADE', options: ['unsigned' => true])]
    private Collection $capabilities;

    private function __construct(?int $id = null)
    {
        $this->id = $id;
        $this->capabilities = new ArrayCollection();
    }

    public static function create(string $code, string $name): self
    {
        $role = new self();
        $role->code = trim($code);
        $role->name = trim($name);
        if ('' === $role->code || '' === $role->name) {
            throw new \InvalidArgumentException('Role code and name are required.');
        }

        return $role;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        return $this->id ?? throw new \LogicException('Role has not been persisted yet.');
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function grant(Capability $capability): void
    {
        if (!$this->capabilities->contains($capability)) {
            $this->capabilities->add($capability);
        }
    }

    public function hasCapability(string $code): bool
    {
        return $this->capabilities->exists(static fn (int $key, Capability $item): bool => $item->code() === $code);
    }
}
