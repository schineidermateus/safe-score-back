<?php

declare(strict_types=1);

namespace App\Credit\Application\DTO;

use App\Credit\Domain\Entity\CreditLimit;

final readonly class CreditLimitOutput
{
    public function __construct(
        public int $id,
        public int $customerId,
        public string $amount,
        public string $validFrom,
        public ?string $validUntil,
        public string $status,
        public string $reason,
        public ?int $approvedByUserId,
        public string $createdAt,
        public string $updatedAt,
    ) {
    }

    public static function fromEntity(CreditLimit $creditLimit): self
    {
        return new self(
            $creditLimit->requireId(),
            $creditLimit->customer()->requireId(),
            $creditLimit->amount(),
            $creditLimit->validFrom()->format('Y-m-d'),
            $creditLimit->validUntil()?->format('Y-m-d'),
            $creditLimit->status()->value,
            $creditLimit->reason(),
            $creditLimit->approvedBy()?->requireId(),
            $creditLimit->createdAt()->format(\DateTimeInterface::ATOM),
            $creditLimit->updatedAt()->format(\DateTimeInterface::ATOM),
        );
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customerId,
            'amount' => $this->amount,
            'valid_from' => $this->validFrom,
            'valid_until' => $this->validUntil,
            'status' => $this->status,
            'reason' => $this->reason,
            'approved_by_user_id' => $this->approvedByUserId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
