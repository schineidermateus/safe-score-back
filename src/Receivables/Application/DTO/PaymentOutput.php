<?php

declare(strict_types=1);

namespace App\Receivables\Application\DTO;

use App\Receivables\Domain\Entity\ReceivablePayment;

final readonly class PaymentOutput
{
    public function __construct(public int $id, public string $amount, public string $paymentDate, public int $createdByUserId, public string $createdAt)
    {
    }

    public static function fromEntity(ReceivablePayment $payment): self
    {
        return new self($payment->requireId(), $payment->amount(), $payment->paymentDate()->format('Y-m-d'), $payment->createdBy()->requireId(), $payment->createdAt()->format(\DateTimeInterface::ATOM));
    }

    /** @return array<string, int|string> */
    public function toArray(): array
    {
        return ['id' => $this->id, 'amount' => $this->amount, 'payment_date' => $this->paymentDate, 'created_by_user_id' => $this->createdByUserId, 'created_at' => $this->createdAt];
    }
}
