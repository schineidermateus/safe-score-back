<?php

declare(strict_types=1);

namespace App\Customers\Domain\Entity;

use App\Customers\Domain\Enum\CustomerStatus;
use App\Customers\Infrastructure\Persistence\Doctrine\DoctrineCustomerRepository;
use App\Organizations\Domain\ValueObject\OrganizationId;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: DoctrineCustomerRepository::class)]
#[ORM\Table(name: 'customer')]
#[ORM\UniqueConstraint(name: 'uniq_customer_organization_document', columns: ['organization_id', 'document'])]
#[ORM\Index(name: 'idx_customer_organization_status', columns: ['organization_id', 'status'])]
#[ORM\Index(name: 'idx_customer_organization_external', columns: ['organization_id', 'external_id'])]
#[ORM\Index(name: 'idx_customer_organization_deleted', columns: ['organization_id', 'deleted_at'])]
class Customer
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 26)]
    private string $id;

    #[ORM\Column(name: 'organization_id', type: 'string', length: 64)]
    private string $organizationId;

    #[ORM\Column(name: 'external_id', type: 'string', length: 100, nullable: true)]
    private ?string $externalId;

    #[ORM\Column(name: 'legal_name', type: 'string', length: 180)]
    private string $legalName;

    #[ORM\Column(name: 'trade_name', type: 'string', length: 180, nullable: true)]
    private ?string $tradeName;

    #[ORM\Column(type: 'string', length: 14, nullable: true)]
    private ?string $document;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $segment;

    #[ORM\Column(type: 'string', length: 20, enumType: CustomerStatus::class)]
    private CustomerStatus $status;

    #[ORM\Column(name: 'account_manager', type: 'string', length: 120, nullable: true)]
    private ?string $accountManager;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(name: 'deleted_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $deletedAt = null;

    private function __construct()
    {
    }

    public static function create(
        OrganizationId $organizationId,
        string $legalName,
        ?string $tradeName,
        ?string $document,
        ?string $externalId,
        ?string $segment,
        ?string $accountManager,
        \DateTimeImmutable $now,
    ): self {
        $customer = new self();
        $customer->id = (string) new Ulid();
        $customer->organizationId = (string) $organizationId;
        $customer->legalName = self::requiredText($legalName);
        $customer->tradeName = self::optionalText($tradeName);
        $customer->document = self::optionalText($document);
        $customer->externalId = self::optionalText($externalId);
        $customer->segment = self::optionalText($segment);
        $customer->accountManager = self::optionalText($accountManager);
        $customer->status = CustomerStatus::Active;
        $customer->createdAt = $now;
        $customer->updatedAt = $now;

        return $customer;
    }

    public function update(
        string $legalName,
        ?string $tradeName,
        ?string $document,
        ?string $externalId,
        ?string $segment,
        ?string $accountManager,
        CustomerStatus $status,
        \DateTimeImmutable $now,
    ): void {
        $this->legalName = self::requiredText($legalName);
        $this->tradeName = self::optionalText($tradeName);
        $this->document = self::optionalText($document);
        $this->externalId = self::optionalText($externalId);
        $this->segment = self::optionalText($segment);
        $this->accountManager = self::optionalText($accountManager);
        $this->status = $status;
        $this->updatedAt = $now;
    }

    public function delete(\DateTimeImmutable $now): void
    {
        if (null !== $this->deletedAt) {
            return;
        }

        $this->deletedAt = $now;
        $this->updatedAt = $now;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function organizationId(): OrganizationId
    {
        return new OrganizationId($this->organizationId);
    }

    public function externalId(): ?string
    {
        return $this->externalId;
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

    public function segment(): ?string
    {
        return $this->segment;
    }

    public function status(): CustomerStatus
    {
        return $this->status;
    }

    public function accountManager(): ?string
    {
        return $this->accountManager;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    private static function requiredText(string $value): string
    {
        $value = trim($value);

        if ('' === $value) {
            throw new \InvalidArgumentException('Legal name cannot be empty.');
        }

        return $value;
    }

    private static function optionalText(?string $value): ?string
    {
        if (null === $value) {
            return null;
        }

        $value = trim($value);

        return '' === $value ? null : $value;
    }
}
