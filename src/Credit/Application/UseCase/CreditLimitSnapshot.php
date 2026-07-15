<?php

declare(strict_types=1);

namespace App\Credit\Application\UseCase;

use App\Credit\Domain\Entity\CreditLimit;

final class CreditLimitSnapshot
{
    /** @return array<string, int|string|null> */
    public static function fromEntity(CreditLimit $creditLimit): array
    {
        return [
            'id' => $creditLimit->requireId(),
            'organization_id' => $creditLimit->organization()->requireId(),
            'customer_id' => $creditLimit->customer()->requireId(),
            'amount' => $creditLimit->amount(),
            'valid_from' => $creditLimit->validFrom()->format('Y-m-d'),
            'valid_until' => $creditLimit->validUntil()?->format('Y-m-d'),
            'status' => $creditLimit->status()->value,
            'reason' => $creditLimit->reason(),
            'approved_by_user_id' => $creditLimit->approvedBy()?->requireId(),
        ];
    }
}
