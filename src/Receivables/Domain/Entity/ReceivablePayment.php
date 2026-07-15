<?php

declare(strict_types=1);

namespace App\Receivables\Domain\Entity;

use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Domain\ValueObject\ReceivableAmount;
use App\Receivables\Infrastructure\Persistence\Doctrine\DoctrineReceivablePaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DoctrineReceivablePaymentRepository::class)]
#[ORM\Table(name: 'receivable_payments')]
#[ORM\Index(name: 'idx_receivable_payment_organization', columns: ['organization_id'])]
#[ORM\Index(name: 'idx_receivable_payment_receivable', columns: ['receivable_id'])]
#[ORM\Index(name: 'idx_receivable_payment_org_receivable_date', columns: ['organization_id', 'receivable_id', 'payment_date', 'id'])]
#[ORM\Index(name: 'idx_receivable_payment_created_by', columns: ['created_by_user_id'])]
class ReceivablePayment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', nullable: false, onDelete: 'RESTRICT', options: ['unsigned' => true])]
    private Organization $organization;

    #[ORM\ManyToOne(targetEntity: Receivable::class)]
    #[ORM\JoinColumn(name: 'receivable_id', nullable: false, onDelete: 'RESTRICT', options: ['unsigned' => true])]
    private Receivable $receivable;

    #[ORM\Column(type: Types::DECIMAL, precision: 19, scale: 2)]
    private string $amount;

    #[ORM\Column(name: 'payment_date', type: Types::DATE_IMMUTABLE)]
    private \DateTimeImmutable $paymentDate;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_user_id', nullable: false, onDelete: 'RESTRICT', options: ['unsigned' => true])]
    private User $createdBy;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    private function __construct(?int $id = null)
    {
        $this->id = $id;
    }

    public static function record(Organization $organization, Receivable $receivable, ReceivableAmount $amount, \DateTimeImmutable $paymentDate, User $createdBy, \DateTimeImmutable $now): self
    {
        if ($receivable->organization() !== $organization) {
            throw new \DomainException('Payment and receivable must belong to the same organization.');
        }
        if (!$amount->isPositive()) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $payment = new self();
        $payment->organization = $organization;
        $payment->receivable = $receivable;
        $payment->amount = (string) $amount;
        $payment->paymentDate = $paymentDate;
        $payment->createdBy = $createdBy;
        $payment->createdAt = $now;

        return $payment;
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function requireId(): int
    {
        return $this->id ?? throw new \LogicException('Receivable payment has not been persisted yet.');
    }

    public function organization(): Organization
    {
        return $this->organization;
    }

    public function receivable(): Receivable
    {
        return $this->receivable;
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function paymentDate(): \DateTimeImmutable
    {
        return $this->paymentDate;
    }

    public function createdBy(): User
    {
        return $this->createdBy;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
