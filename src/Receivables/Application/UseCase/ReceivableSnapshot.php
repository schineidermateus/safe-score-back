<?php

declare(strict_types=1);

namespace App\Receivables\Application\UseCase;

use App\Receivables\Domain\Entity\Receivable;

final class ReceivableSnapshot
{
    /** @return array<string, mixed> */
    public static function fromEntity(Receivable $receivable): array
    {
        return ['customer_id' => $receivable->customer()->requireId(), 'source' => $receivable->source(), 'external_id' => $receivable->externalId(),
            'document_number' => $receivable->documentNumber(), 'issue_date' => $receivable->issueDate()->format('Y-m-d'), 'due_date' => $receivable->dueDate()->format('Y-m-d'),
            'original_amount' => $receivable->originalAmount(), 'open_amount' => $receivable->openAmount(), 'paid_amount' => $receivable->paidAmount(),
            'payment_date' => $receivable->paymentDate()?->format('Y-m-d'), 'status' => $receivable->status()->value,
            'cancelled_at' => $receivable->cancelledAt()?->format(\DateTimeInterface::ATOM),
            'cancelled_by_user_id' => $receivable->cancelledBy()?->requireId(), 'cancellation_reason' => $receivable->cancellationReason()];
    }
}
