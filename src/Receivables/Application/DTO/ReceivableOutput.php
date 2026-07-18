<?php

declare(strict_types=1);

namespace App\Receivables\Application\DTO;

use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Entity\ReceivablePayment;
use App\Receivables\Domain\Service\AgingClassifier;
use App\Receivables\Domain\Service\ReceivableStatusResolverInterface;

final readonly class ReceivableOutput
{
    /** @param list<PaymentOutput> $payments */
    public function __construct(
        public int $id, public int $customerId, public string $customerName, public string $source, public ?string $externalId,
        public string $documentNumber, public string $issueDate, public string $dueDate, public string $originalAmount,
        public string $openAmount, public string $paidAmount, public string $currency, public ?string $paymentDate,
        public string $status, public ?string $agingBucket, public ?string $cancelledAt, public ?int $cancelledByUserId,
        public ?string $cancellationReason, public string $createdAt, public string $updatedAt, public array $payments,
    ) {
    }

    /** @param list<ReceivablePayment> $payments */
    public static function fromEntity(Receivable $receivable, \DateTimeImmutable $referenceDate, ReceivableStatusResolverInterface $resolver, AgingClassifier $aging, array $payments = []): self
    {
        $status = $resolver->resolve($receivable, $referenceDate);

        return new self(
            $receivable->requireId(), $receivable->customer()->requireId(), $receivable->customer()->legalName(), $receivable->source(),
            $receivable->externalId(), $receivable->documentNumber(), $receivable->issueDate()->format('Y-m-d'), $receivable->dueDate()->format('Y-m-d'),
            $receivable->originalAmount(), $receivable->openAmount(), $receivable->paidAmount(), $receivable->organization()->currency(),
            $receivable->paymentDate()?->format('Y-m-d'), $status->value, $aging->classify($receivable, $referenceDate)?->value,
            $receivable->cancelledAt()?->format(\DateTimeInterface::ATOM), $receivable->cancelledBy()?->requireId(), $receivable->cancellationReason(),
            $receivable->createdAt()->format(\DateTimeInterface::ATOM), $receivable->updatedAt()->format(\DateTimeInterface::ATOM),
            array_map(PaymentOutput::fromEntity(...), $payments),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['id' => $this->id, 'customer_id' => $this->customerId, 'customer_name' => $this->customerName, 'source' => $this->source,
            'external_id' => $this->externalId, 'document_number' => $this->documentNumber, 'issue_date' => $this->issueDate, 'due_date' => $this->dueDate,
            'original_amount' => $this->originalAmount, 'open_amount' => $this->openAmount, 'paid_amount' => $this->paidAmount, 'currency' => $this->currency,
            'payment_date' => $this->paymentDate, 'status' => $this->status, 'aging_bucket' => $this->agingBucket, 'cancelled_at' => $this->cancelledAt,
            'cancelled_by_user_id' => $this->cancelledByUserId, 'cancellation_reason' => $this->cancellationReason, 'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt, 'payments' => array_map(static fn (PaymentOutput $payment): array => $payment->toArray(), $this->payments)];
    }
}
