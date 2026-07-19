<?php

declare(strict_types=1);

namespace App\Industrial\Domain\Entity;

use App\Industrial\Domain\Enum\BusinessPartnerType;
use App\Industrial\Domain\Enum\FoundationStatus;
use App\Organizations\Domain\Entity\Organization;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'business_partners')]
#[ORM\UniqueConstraint(name: 'uniq_business_partner_org_code', columns: ['organization_id', 'code'])]
#[ORM\Index(name: 'idx_business_partner_org_status', columns: ['organization_id', 'status'])]
class BusinessPartner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;
    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', nullable: false, onDelete: 'RESTRICT', options: ['unsigned' => true])]
    private Organization $organization;
    #[ORM\Column(type: Types::STRING, length: 100)] private string $code;
    #[ORM\Column(name: 'legal_name', type: Types::STRING, length: 180)] private string $legalName;
    #[ORM\Column(name: 'trade_name', type: Types::STRING, length: 180, nullable: true)] private ?string $tradeName;
    #[ORM\Column(type: Types::STRING, length: 40, enumType: BusinessPartnerType::class)] private BusinessPartnerType $type;
    #[ORM\Column(type: Types::STRING, length: 20, enumType: FoundationStatus::class)] private FoundationStatus $status;
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)] private \DateTimeImmutable $createdAt;
    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)] private \DateTimeImmutable $updatedAt;
    private function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    public static function create(Organization $organization, string $code, string $legalName, ?string $tradeName, BusinessPartnerType $type, \DateTimeImmutable $now): self
    {
        $entity = new self();
        $entity->organization = $organization;
        $entity->code = self::required($code);
        $entity->legalName = self::required($legalName);
        $entity->tradeName = null === $tradeName || '' === trim($tradeName) ? null : trim($tradeName);
        $entity->type = $type;
        $entity->status = FoundationStatus::Active;
        $entity->createdAt = $now;
        $entity->updatedAt = $now;

        return $entity;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        return $this->id ?? throw new \LogicException('Business partner has not been persisted yet.');
    }

    public function organization(): Organization
    {
        return $this->organization;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function legalName(): string
    {
        return $this->legalName;
    }

    public function tradeName(): ?string
    {
        return $this->tradeName;
    }

    public function type(): BusinessPartnerType
    {
        return $this->type;
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

    private static function required(string $value): string
    {
        $value = trim($value);
        if ('' === $value) {
            throw new \InvalidArgumentException('Value is required.');
        }

return $value;
    }
}
