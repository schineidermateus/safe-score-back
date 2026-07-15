<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Support;

use App\Organizations\Domain\Entity\Organization;
use App\Reporting\Domain\Model\ReceivableFinancialAggregate;
use App\Reporting\Domain\Repository\FinancialIndicatorsQueryInterface;
use App\Reporting\Domain\ValueObject\DecimalAmount;
use App\Reporting\Domain\ValueObject\ReferenceDate;

final class InMemoryFinancialIndicatorsQuery implements FinancialIndicatorsQueryInterface
{
    /** @var array<int, ReceivableFinancialAggregate> */
    public array $aggregates = [];

    /** @var array<int, \DateTimeImmutable> */
    public array $creditUpdates = [];

    public ?\DateTimeImmutable $lastImport = null;
    public int $organizationAggregateCalls = 0;
    public int $totalExposureCalls = 0;

    /** @var list<string> */
    public array $referenceDates = [];

    public function aggregateForCustomer(Organization $organization, int $customerId, ReferenceDate $referenceDate): ReceivableFinancialAggregate
    {
        $this->referenceDates[] = (string) $referenceDate;

        return $this->aggregates[$customerId] ?? ReceivableFinancialAggregate::empty($customerId);
    }

    public function aggregatesForOrganization(Organization $organization, ReferenceDate $referenceDate): array
    {
        ++$this->organizationAggregateCalls;
        $this->referenceDates[] = (string) $referenceDate;

        return $this->aggregates;
    }

    public function totalExposureForOrganization(Organization $organization): DecimalAmount
    {
        ++$this->totalExposureCalls;
        $total = DecimalAmount::zero();
        foreach ($this->aggregates as $aggregate) {
            $total = $total->add($aggregate->exposure);
        }

        return $total;
    }

    public function lastCreditLimitUpdateForCustomer(Organization $organization, int $customerId): ?\DateTimeImmutable
    {
        return $this->creditUpdates[$customerId] ?? null;
    }

    public function lastCreditLimitUpdatesForOrganization(Organization $organization): array
    {
        return $this->creditUpdates;
    }

    public function lastCompletedFinancialImport(Organization $organization): ?\DateTimeImmutable
    {
        return $this->lastImport;
    }
}
