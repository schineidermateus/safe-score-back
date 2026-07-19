<?php

declare(strict_types=1);

namespace App\Authorization\Domain\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'capabilities')]
#[ORM\UniqueConstraint(name: 'uniq_capability_code', columns: ['code'])]
class Capability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $code;

    #[ORM\Column(type: Types::STRING, length: 180)]
    private string $description;

    private function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    public static function create(string $code, string $description): self
    {
        $capability = new self();
        $capability->code = trim($code);
        $capability->description = trim($description);

        if ('' === $capability->code || '' === $capability->description) {
            throw new \InvalidArgumentException('Capability code and description are required.');
        }

        return $capability;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        return $this->id ?? throw new \LogicException('Capability has not been persisted yet.');
    }

    public function code(): string
    {
        return $this->code;
    }

    public function description(): string
    {
        return $this->description;
    }
}
