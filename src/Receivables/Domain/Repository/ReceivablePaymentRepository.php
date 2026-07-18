<?php

declare(strict_types=1);

namespace App\Receivables\Domain\Repository;

use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Entity\ReceivablePayment;

interface ReceivablePaymentRepository
{
    public function save(Organization $organization, ReceivablePayment $payment): void;

    /** @return list<ReceivablePayment> */
    public function listByReceivableAndOrganization(Receivable $receivable, Organization $organization): array;
}
