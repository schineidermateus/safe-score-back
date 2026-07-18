<?php

declare(strict_types=1);

namespace App\Organizations\Domain\Entity;

use App\Organizations\Domain\Enum\OrganizationStatus;
use App\Organizations\Infrastructure\Persistence\Doctrine\DoctrineOrganizationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineOrganizationRepository::class)]
#[ORM\Table(name: 'organization')]
#[ORM\UniqueConstraint(name: 'uniq_organization_document', columns: ['document'])]
class Organization
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(name: 'legal_name', type: Types::STRING, length: 180)]
    private string $legalName;

    #[ORM\Column(name: 'trade_name', type: Types::STRING, length: 180, nullable: true)]
    private ?string $tradeName;

    #[ORM\Column(type: Types::STRING, length: 14, nullable: true)]
    private ?string $document;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: OrganizationStatus::class)]
    private OrganizationStatus $status;

    #[ORM\Column(type: Types::STRING, length: 64)]
    private string $timezone;

    #[ORM\Column(type: Types::STRING, length: 3)]
    private string $currency;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    private function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    public static function create(
        string $legalName,
        ?string $tradeName,
        ?string $document,
        \DateTimeImmutable $now,
        string $timezone = 'America/Sao_Paulo',
        string $currency = 'BRL',
    ): self {
        $organization = new self();
        $organization->legalName = self::required($legalName, 'Legal name');
        $organization->tradeName = self::optional($tradeName);
        $organization->document = self::normalizeDocument($document);
        $organization->status = OrganizationStatus::Active;
        $organization->timezone = self::required($timezone, 'Timezone');
        $organization->currency = strtoupper(self::required($currency, 'Currency'));
        $organization->createdAt = $now;
        $organization->updatedAt = $now;

        return $organization;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        return $this->id ?? throw new \LogicException('Organization has not been persisted yet.');
    }

    public function legalName(): string
    {
        return $this->legalName;
    }

    public function tradeName(): ?string
    {
        return $this->tradeName;
    }

    public function document(): ?string
    {
        return $this->document;
    }

    public function status(): OrganizationStatus
    {
        return $this->status;
    }

    public function isActive(): bool
    {
        return OrganizationStatus::Active === $this->status;
    }

    public function timezone(): string
    {
        return $this->timezone;
    }

    public function currency(): string
    {
        return $this->currency;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function normalizeDocument(?string $document): ?string
    {
        if (null === $document || '' === trim($document)) {
            return null;
        }

        $normalized = preg_replace('/\D/', '', $document) ?? '';
        if (!in_array(strlen($normalized), [11, 14], true)) {
            throw new \InvalidArgumentException('Invalid organization document.');
        }

        return $normalized;
    }

    private static function required(string $value, string $field): string
    {
        $value = trim($value);
        if ('' === $value) {
            throw new \InvalidArgumentException($field.' cannot be empty.');
        }

        return $value;
    }

    private static function optional(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }
}
