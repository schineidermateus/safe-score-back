<?php

declare(strict_types=1);

namespace App\Credit\Domain\Entity;

use App\Credit\Domain\Enum\CreditLimitStatus;
use App\Credit\Domain\ValueObject\MoneyAmount;
use App\Credit\Infrastructure\Persistence\Doctrine\DoctrineCreditLimitRepository;
use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineCreditLimitRepository::class)]
#[ORM\Table(name: 'credit_limits')]
#[ORM\Index(name: 'idx_credit_limit_org_customer_period', columns: ['organization_id', 'customer_id', 'status', 'valid_from', 'valid_until'])]
#[ORM\Index(name: 'idx_credit_limit_org_customer_history', columns: ['organization_id', 'customer_id', 'created_at', 'id'])]
#[ORM\Index(name: 'idx_credit_limit_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_credit_limit_customer', columns: ['customer_id'])]
#[ORM\Index(name: 'idx_credit_limit_org_status', columns: ['organization_id', 'status'])]
#[ORM\Index(name: 'idx_credit_limit_approved_by', columns: ['approved_by_user_id'])]
class CreditLimit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', nullable: false, onDelete: 'RESTRICT', options: ['unsigned' => true])]
    private Organization $organization;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(name: 'customer_id', nullable: false, onDelete: 'RESTRICT', options: ['unsigned' => true])]
    private Customer $customer;

    #[ORM\Column(type: Types::DECIMAL, precision: 19, scale: 2)]
    private string $amount;

    #[ORM\Column(name: 'valid_from', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $validFrom;

    #[ORM\Column(name: 'valid_until', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $validUntil;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: CreditLimitStatus::class)]
    private CreditLimitStatus $status;

    #[ORM\Column(type: Types::STRING, length: 1000)]
    private string $reason;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'approved_by_user_id', nullable: true, onDelete: 'RESTRICT', options: ['unsigned' => true])]
    private ?User $approvedBy;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    private function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    public static function createActive(
        Organization $organization,
        Customer $customer,
        MoneyAmount $amount,
        \DateTimeImmutable $validFrom,
        ?\DateTimeImmutable $validUntil,
        string $reason,
        User $approvedBy,
        \DateTimeImmutable $now,
    ): self {
        return self::create(
            $organization,
            $customer,
            $amount,
            $validFrom,
            $validUntil,
            $reason,
            CreditLimitStatus::Active,
            $approvedBy,
            $now,
        );
    }

    public static function createDraft(
        Organization $organization,
        Customer $customer,
        MoneyAmount $amount,
        \DateTimeImmutable $validFrom,
        ?\DateTimeImmutable $validUntil,
        string $reason,
        \DateTimeImmutable $now,
    ): self {
        return self::create(
            $organization,
            $customer,
            $amount,
            $validFrom,
            $validUntil,
            $reason,
            CreditLimitStatus::Draft,
            null,
            $now,
        );
    }

    public function update(
        MoneyAmount $amount,
        \DateTimeImmutable $validFrom,
        ?\DateTimeImmutable $validUntil,
        string $reason,
        \DateTimeImmutable $now,
    ): void {
        if (!in_array($this->status, [CreditLimitStatus::Draft, CreditLimitStatus::Active], true)) {
            throw new \DomainException('Only draft or active credit limits can be updated.');
        }

        self::validatePeriod($validFrom, $validUntil);
        $this->amount = (string) $amount;
        $this->validFrom = $validFrom;
        $this->validUntil = $validUntil;
        $this->reason = self::requiredReason($reason);
        $this->updatedAt = $now;
    }

    public function activate(User $approvedBy, \DateTimeImmutable $now): void
    {
        if (CreditLimitStatus::Draft !== $this->status) {
            throw new \DomainException('Only draft credit limits can be activated.');
        }

        $this->status = CreditLimitStatus::Active;
        $this->approvedBy = $approvedBy;
        $this->updatedAt = $now;
    }

    public function expire(\DateTimeImmutable $now): void
    {
        if (CreditLimitStatus::Active !== $this->status) {
            throw new \DomainException('Only active credit limits can expire.');
        }

        $this->status = CreditLimitStatus::Expired;
        $this->updatedAt = $now;
    }

    public function revoke(string $reason, \DateTimeImmutable $now): void
    {
        if (CreditLimitStatus::Revoked === $this->status) {
            throw new \DomainException('Credit limit is already revoked.');
        }
        if (CreditLimitStatus::Expired === $this->status) {
            throw new \DomainException('Expired credit limits cannot be revoked.');
        }

        $this->status = CreditLimitStatus::Revoked;
        $this->reason = self::requiredReason($reason);
        $this->updatedAt = $now;
    }

    public function isApplicableAt(\DateTimeImmutable $referenceDate): bool
    {
        return CreditLimitStatus::Active === $this->status
            && $this->validFrom <= $referenceDate
            && (null === $this->validUntil || $this->validUntil >= $referenceDate);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        return $this->id ?? throw new \LogicException('Credit limit has not been persisted yet.');
    }

    public function organization(): Organization
    {
        return $this->organization;
    }

    public function customer(): Customer
    {
        return $this->customer;
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function validFrom(): \DateTimeImmutable
    {
        return $this->validFrom;
    }

    public function validUntil(): ?\DateTimeImmutable
    {
        return $this->validUntil;
    }

    public function status(): CreditLimitStatus
    {
        return $this->status;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function approvedBy(): ?User
    {
        return $this->approvedBy;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function create(
        Organization $organization,
        Customer $customer,
        MoneyAmount $amount,
        \DateTimeImmutable $validFrom,
        ?\DateTimeImmutable $validUntil,
        string $reason,
        CreditLimitStatus $status,
        ?User $approvedBy,
        \DateTimeImmutable $now,
    ): self {
        if ($customer->organization() !== $organization) {
            throw new \DomainException('Customer and credit limit must belong to the same organization.');
        }
        self::validatePeriod($validFrom, $validUntil);

        $creditLimit = new self();
        $creditLimit->organization = $organization;
        $creditLimit->customer = $customer;
        $creditLimit->amount = (string) $amount;
        $creditLimit->validFrom = $validFrom;
        $creditLimit->validUntil = $validUntil;
        $creditLimit->status = $status;
        $creditLimit->reason = self::requiredReason($reason);
        $creditLimit->approvedBy = $approvedBy;
        $creditLimit->createdAt = $now;
        $creditLimit->updatedAt = $now;

        return $creditLimit;
    }

    private static function validatePeriod(\DateTimeImmutable $validFrom, ?\DateTimeImmutable $validUntil): void
    {
        if (null !== $validUntil && $validUntil < $validFrom) {
            throw new \DomainException('Credit limit end date cannot be before its start date.');
        }
    }

    private static function requiredReason(string $reason): string
    {
        $reason = trim($reason);
        if ('' === $reason) {
            throw new \InvalidArgumentException('Credit limit reason is required.');
        }
        if (mb_strlen($reason) > 1000) {
            throw new \InvalidArgumentException('Credit limit reason cannot exceed 1000 characters.');
        }

        return $reason;
    }
}
