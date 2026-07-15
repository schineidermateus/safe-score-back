<?php

declare(strict_types=1);

namespace App\Receivables\Domain\Entity;

use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Domain\Enum\ReceivableStatus;
use App\Receivables\Domain\ValueObject\ReceivableAmount;
use App\Receivables\Infrastructure\Persistence\Doctrine\DoctrineReceivableRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineReceivableRepository::class)]
#[ORM\Table(name: 'receivables')]
#[ORM\UniqueConstraint(name: 'uniq_receivable_org_source_external', columns: ['organization_id', 'source', 'external_id'])]
#[ORM\Index(name: 'idx_receivable_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_receivable_org_customer_due', columns: ['organization_id', 'customer_id', 'due_date', 'id'])]
#[ORM\Index(name: 'idx_receivable_org_due', columns: ['organization_id', 'due_date', 'id'])]
#[ORM\Index(name: 'idx_receivable_org_status_due', columns: ['organization_id', 'status', 'due_date'])]
#[ORM\Index(name: 'idx_receivable_customer', columns: ['customer_id'])]
#[ORM\Index(name: 'idx_receivable_cancelled_by', columns: ['cancelled_by_user_id'])]
class Receivable
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

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $source;

    #[ORM\Column(name: 'external_id', type: Types::STRING, length: 150, nullable: true)]
    private ?string $externalId;

    #[ORM\Column(name: 'document_number', type: Types::STRING, length: 100)]
    private string $documentNumber;

    #[ORM\Column(name: 'issue_date', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $issueDate;

    #[ORM\Column(name: 'due_date', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $dueDate;

    #[ORM\Column(name: 'original_amount', type: Types::DECIMAL, precision: 19, scale: 2)]
    private string $originalAmount;

    #[ORM\Column(name: 'open_amount', type: Types::DECIMAL, precision: 19, scale: 2)]
    private string $openAmount;

    #[ORM\Column(name: 'paid_amount', type: Types::DECIMAL, precision: 19, scale: 2)]
    private string $paidAmount;

    #[ORM\Column(name: 'payment_date', type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $paymentDate = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: ReceivableStatus::class)]
    private ReceivableStatus $status;

    #[ORM\Column(name: 'cancelled_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $cancelledAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'cancelled_by_user_id', nullable: true, onDelete: 'RESTRICT', options: ['unsigned' => true])]
    private ?User $cancelledBy = null;

    #[ORM\Column(name: 'cancellation_reason', type: Types::STRING, length: 1000, nullable: true)]
    private ?string $cancellationReason = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    private function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    public static function create(
        Organization $organization,
        Customer $customer,
        string $source,
        ?string $externalId,
        string $documentNumber,
        \DateTimeImmutable $issueDate,
        \DateTimeImmutable $dueDate,
        ReceivableAmount $originalAmount,
        \DateTimeImmutable $now,
    ): self {
        if ($customer->organization() !== $organization) {
            throw new \DomainException('Customer and receivable must belong to the same organization.');
        }
        self::validateDates($issueDate, $dueDate);

        $receivable = new self();
        $receivable->organization = $organization;
        $receivable->customer = $customer;
        $receivable->source = self::required($source, 50, 'Source');
        $receivable->externalId = self::optional($externalId, 150, 'External id');
        $receivable->documentNumber = self::required($documentNumber, 100, 'Document number');
        $receivable->issueDate = $issueDate;
        $receivable->dueDate = $dueDate;
        $receivable->originalAmount = (string) $originalAmount;
        $receivable->openAmount = (string) $originalAmount;
        $receivable->paidAmount = '0.00';
        $receivable->status = $originalAmount->isZero() ? ReceivableStatus::Paid : ReceivableStatus::Open;
        $receivable->paymentDate = null;
        $receivable->createdAt = $now;
        $receivable->updatedAt = $now;

        return $receivable;
    }

    public function update(
        string $documentNumber,
        \DateTimeImmutable $issueDate,
        \DateTimeImmutable $dueDate,
        ReceivableAmount $originalAmount,
        \DateTimeImmutable $now,
    ): void {
        if (ReceivableStatus::Cancelled === $this->status || ReceivableStatus::Paid === $this->status) {
            throw new \DomainException('Cancelled or paid receivables cannot be updated.');
        }
        if ('0.00' !== $this->paidAmount && (string) $originalAmount !== $this->originalAmount) {
            throw new \DomainException('Original amount cannot change after a payment.');
        }
        self::validateDates($issueDate, $dueDate);

        $this->documentNumber = self::required($documentNumber, 100, 'Document number');
        $this->issueDate = $issueDate;
        $this->dueDate = $dueDate;
        if ('0.00' === $this->paidAmount) {
            $this->originalAmount = (string) $originalAmount;
            $this->openAmount = (string) $originalAmount;
            $this->status = $originalAmount->isZero() ? ReceivableStatus::Paid : ReceivableStatus::Open;
            $this->paymentDate = null;
        }
        $this->updatedAt = $now;
    }

    public function registerPayment(ReceivableAmount $amount, \DateTimeImmutable $paymentDate, User $user, \DateTimeImmutable $now): ReceivablePayment
    {
        if (ReceivableStatus::Cancelled === $this->status || ReceivableStatus::Paid === $this->status) {
            throw new \DomainException('Payment is not allowed for the current receivable status.');
        }
        if (!$amount->isPositive()) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }
        if ($paymentDate < $this->issueDate) {
            throw new \InvalidArgumentException('Payment date cannot precede issue date.');
        }

        $open = new ReceivableAmount($this->openAmount);
        if ($amount->compare($open) > 0) {
            throw new \DomainException('Payment amount exceeds the open balance.');
        }

        $this->openAmount = (string) $open->subtract($amount);
        $this->paidAmount = (string) (new ReceivableAmount($this->paidAmount))->add($amount);
        $this->status = '0.00' === $this->openAmount ? ReceivableStatus::Paid : ReceivableStatus::PartiallyPaid;
        $this->paymentDate = ReceivableStatus::Paid === $this->status ? $paymentDate : null;
        $this->updatedAt = $now;

        return ReceivablePayment::record($this->organization, $this, $amount, $paymentDate, $user, $now);
    }

    public function cancel(User $user, string $reason, \DateTimeImmutable $now): void
    {
        if (ReceivableStatus::Cancelled === $this->status) {
            throw new \DomainException('Receivable is already cancelled.');
        }
        if (ReceivableStatus::Paid === $this->status) {
            throw new \DomainException('Paid receivables cannot be cancelled.');
        }

        $this->status = ReceivableStatus::Cancelled;
        $this->cancelledAt = $now;
        $this->cancelledBy = $user;
        $this->cancellationReason = self::required($reason, 1000, 'Cancellation reason');
        $this->updatedAt = $now;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        return $this->id ?? throw new \LogicException('Receivable has not been persisted yet.');
    }

    public function organization(): Organization
    {
        return $this->organization;
    }

    public function customer(): Customer
    {
        return $this->customer;
    }

    public function source(): string
    {
        return $this->source;
    }

    public function externalId(): ?string
    {
        return $this->externalId;
    }

    public function documentNumber(): string
    {
        return $this->documentNumber;
    }

    public function issueDate(): \DateTimeImmutable
    {
        return $this->issueDate;
    }

    public function dueDate(): \DateTimeImmutable
    {
        return $this->dueDate;
    }

    public function originalAmount(): string
    {
        return $this->originalAmount;
    }

    public function openAmount(): string
    {
        return $this->openAmount;
    }

    public function paidAmount(): string
    {
        return $this->paidAmount;
    }

    public function paymentDate(): ?\DateTimeImmutable
    {
        return $this->paymentDate;
    }

    public function status(): ReceivableStatus
    {
        return $this->status;
    }

    public function cancelledAt(): ?\DateTimeImmutable
    {
        return $this->cancelledAt;
    }

    public function cancelledBy(): ?User
    {
        return $this->cancelledBy;
    }

    public function cancellationReason(): ?string
    {
        return $this->cancellationReason;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private static function validateDates(\DateTimeImmutable $issueDate, \DateTimeImmutable $dueDate): void
    {
        if ($dueDate < $issueDate) {
            throw new \DomainException('Due date cannot precede issue date.');
        }
    }

    private static function required(string $value, int $maxLength, string $field): string
    {
        $value = trim($value);
        if ('' === $value || mb_strlen($value) > $maxLength) {
            throw new \InvalidArgumentException($field.' is required and exceeds its allowed length.');
        }

        return 'Source' === $field ? strtoupper($value) : $value;
    }

    private static function optional(?string $value, int $maxLength, string $field): ?string
    {
        if (null === $value || '' === trim($value)) {
            return null;
        }
        $value = trim($value);
        if (mb_strlen($value) > $maxLength) {
            throw new \InvalidArgumentException($field.' exceeds its allowed length.');
        }

        return $value;
    }
}
