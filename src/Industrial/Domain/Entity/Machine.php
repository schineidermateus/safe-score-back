<?php

declare(strict_types=1);

namespace App\Industrial\Domain\Entity;

use App\Industrial\Domain\Enum\FoundationStatus;
use App\Organizations\Domain\Entity\Organization;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity] #[ORM\Table(name: 'machines')] #[ORM\UniqueConstraint(name: 'uniq_machine_org_code', columns: ['organization_id', 'code'])]
class Machine
{
    #[ORM\Id] #[ORM\GeneratedValue] #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])] private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: Organization::class)] #[ORM\JoinColumn(name: 'organization_id', nullable: false, onDelete: 'RESTRICT', options: ['unsigned' => true])] private Organization $organization;
    #[ORM\Column(type: Types::STRING, length: 100)] private string $code;
    #[ORM\Column(type: Types::STRING, length: 180)] private string $name;
    #[ORM\Column(type: Types::STRING, length: 20, enumType: FoundationStatus::class)] private FoundationStatus $status;
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)] private \DateTimeImmutable $createdAt;
    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)] private \DateTimeImmutable $updatedAt;
    private function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    public static function create(Organization $o, string $c, string $n, \DateTimeImmutable $now): self
    {
        $e = new self();
        $e->organization = $o;
        $e->code = trim($c);
        $e->name = trim($n);
        if ('' === $e->code || '' === $e->name) {
            throw new \InvalidArgumentException('Code and name are required.');
        }$e->status = FoundationStatus::Active;
        $e->createdAt = $now;
        $e->updatedAt = $now;

        return $e;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function organization(): Organization
    {
        return $this->organization;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function status(): FoundationStatus
    {
        return $this->status;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
