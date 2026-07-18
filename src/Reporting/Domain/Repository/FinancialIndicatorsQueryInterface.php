<?php

declare(strict_types=1);

namespace App\Reporting\Domain\Repository;

use App\Organizations\Domain\Entity\Organization;
use App\Reporting\Domain\Model\ReceivableFinancialAggregate;
use App\Reporting\Domain\ValueObject\DecimalAmount;
use App\Reporting\Domain\ValueObject\ReferenceDate;

interface FinancialIndicatorsQueryInterface
{
    public function aggregateForCustomer(Organization $organization, int $customerId, ReferenceDate $referenceDate): ReceivableFinancialAggregate;

    /** @return array<int, ReceivableFinancialAggregate> */
    public function aggregatesForOrganization(Organization $organization, ReferenceDate $referenceDate): array;

    public function totalExposureForOrganization(Organization $organization): DecimalAmount;

    public function lastCreditLimitUpdateForCustomer(Organization $organization, int $customerId): ?\DateTimeImmutable;

    /** @return array<int, \DateTimeImmutable> */
    public function lastCreditLimitUpdatesForOrganization(Organization $organization): array;

    public function lastCompletedFinancialImport(Organization $organization): ?\DateTimeImmutable;
}
