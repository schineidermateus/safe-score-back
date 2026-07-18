<?php

declare(strict_types=1);

namespace App\Reporting\Infrastructure\Persistence\Doctrine;

use App\Organizations\Domain\Entity\Organization;
use App\Reporting\Domain\Model\ReceivableFinancialAggregate;
use App\Reporting\Domain\Repository\FinancialIndicatorsQueryInterface;
use App\Reporting\Domain\ValueObject\DecimalAmount;
use App\Reporting\Domain\ValueObject\ReferenceDate;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

final readonly class DoctrineFinancialIndicatorsQuery implements FinancialIndicatorsQueryInterface
{
    private Connection $connection;

    public function __construct(ManagerRegistry $registry)
    {
        $connection = $registry->getConnection();
        if (!$connection instanceof Connection) {
            throw new \LogicException('The financial indicators query requires a Doctrine DBAL connection.');
        }
        $this->connection = $connection;
    }

    public function aggregateForCustomer(Organization $organization, int $customerId, ReferenceDate $referenceDate): ReceivableFinancialAggregate
    {
        $row = $this->connection->fetchAssociative(
            $this->aggregateSql('AND receivable.customer_id = :customerId'),
            ['organizationId' => $organization->requireId(), 'customerId' => $customerId, 'referenceDate' => $referenceDate->toDateTimeImmutable()],
            ['referenceDate' => Types::DATE_IMMUTABLE],
        );

        return false === $row ? ReceivableFinancialAggregate::empty($customerId) : $this->hydrate($row);
    }

    public function aggregatesForOrganization(Organization $organization, ReferenceDate $referenceDate): array
    {
        $rows = $this->connection->fetchAllAssociative(
            $this->aggregateSql(''),
            ['organizationId' => $organization->requireId(), 'referenceDate' => $referenceDate->toDateTimeImmutable()],
            ['referenceDate' => Types::DATE_IMMUTABLE],
        );
        $result = [];
        foreach ($rows as $row) {
            $aggregate = $this->hydrate($row);
            $result[$aggregate->customerId] = $aggregate;
        }

        return $result;
    }

    public function totalExposureForOrganization(Organization $organization): DecimalAmount
    {
        $value = $this->connection->fetchOne(
            "SELECT COALESCE(SUM(CASE WHEN receivable.status != 'CANCELLED' AND receivable.open_amount > 0 THEN receivable.open_amount ELSE 0 END), 0.00)
             FROM receivables receivable
             INNER JOIN customer customer ON customer.id = receivable.customer_id AND customer.organization_id = :organizationId AND customer.deleted_at IS NULL
             WHERE receivable.organization_id = :organizationId",
            ['organizationId' => $organization->requireId()],
        );

        return new DecimalAmount((string) $value);
    }

    public function lastCreditLimitUpdateForCustomer(Organization $organization, int $customerId): ?\DateTimeImmutable
    {
        $value = $this->connection->fetchOne(
            'SELECT MAX(credit_limit.updated_at)
             FROM credit_limits credit_limit
             INNER JOIN customer customer ON customer.id = credit_limit.customer_id AND customer.organization_id = :organizationId AND customer.deleted_at IS NULL
             WHERE credit_limit.organization_id = :organizationId AND credit_limit.customer_id = :customerId',
            ['organizationId' => $organization->requireId(), 'customerId' => $customerId],
        );

        return $this->dateTimeOrNull($value);
    }

    public function lastCreditLimitUpdatesForOrganization(Organization $organization): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT credit_limit.customer_id, MAX(credit_limit.updated_at) AS last_update
             FROM credit_limits credit_limit
             INNER JOIN customer customer ON customer.id = credit_limit.customer_id AND customer.organization_id = :organizationId AND customer.deleted_at IS NULL
             WHERE credit_limit.organization_id = :organizationId
             GROUP BY credit_limit.customer_id',
            ['organizationId' => $organization->requireId()],
        );
        $result = [];
        foreach ($rows as $row) {
            $updatedAt = $this->dateTimeOrNull($row['last_update'] ?? null);
            if (null !== $updatedAt) {
                $result[(int) $row['customer_id']] = $updatedAt;
            }
        }

        return $result;
    }

    public function lastCompletedFinancialImport(Organization $organization): ?\DateTimeImmutable
    {
        $value = $this->connection->fetchOne(
            "SELECT MAX(completed_at) FROM import_batches WHERE organization_id = :organizationId AND type IN ('CREDIT_LIMITS', 'RECEIVABLES') AND status IN ('COMPLETED', 'COMPLETED_WITH_ERRORS')",
            ['organizationId' => $organization->requireId()],
        );

        return $this->dateTimeOrNull($value);
    }

    private function aggregateSql(string $customerFilter): string
    {
        return <<<SQL
            SELECT
                receivable.customer_id,
                COALESCE(SUM(CASE WHEN receivable.status != 'CANCELLED' AND receivable.open_amount > 0 THEN receivable.open_amount ELSE 0 END), 0.00) AS exposure,
                COALESCE(SUM(CASE WHEN receivable.status != 'CANCELLED' AND receivable.open_amount > 0 AND receivable.due_date < :referenceDate THEN receivable.open_amount ELSE 0 END), 0.00) AS overdue_exposure,
                COALESCE(MAX(CASE WHEN receivable.status != 'CANCELLED' AND receivable.open_amount > 0 AND receivable.due_date < :referenceDate THEN DATEDIFF(:referenceDate, receivable.due_date) ELSE 0 END), 0) AS maximum_overdue_days,
                SUM(CASE WHEN receivable.status != 'CANCELLED' THEN 1 ELSE 0 END) AS receivables_count,
                SUM(CASE WHEN receivable.status = 'PAID' AND receivable.open_amount = 0 AND receivable.payment_date IS NOT NULL AND receivable.payment_date <= :referenceDate THEN 1 ELSE 0 END) AS paid_count,
                SUM(CASE WHEN receivable.status = 'PAID' AND receivable.open_amount = 0 AND receivable.payment_date IS NOT NULL AND receivable.payment_date <= :referenceDate AND receivable.payment_date <= receivable.due_date THEN 1 ELSE 0 END) AS on_time_count,
                SUM(CASE WHEN receivable.status = 'PAID' AND receivable.open_amount = 0 AND receivable.payment_date IS NOT NULL AND receivable.payment_date <= :referenceDate AND receivable.payment_date > receivable.due_date THEN 1 ELSE 0 END) AS late_count,
                COALESCE(SUM(CASE WHEN receivable.status = 'PAID' AND receivable.open_amount = 0 AND receivable.payment_date IS NOT NULL AND receivable.payment_date <= :referenceDate THEN GREATEST(DATEDIFF(receivable.payment_date, receivable.due_date), 0) ELSE 0 END), 0) AS total_payment_delay_days,
                COALESCE(MAX(CASE WHEN receivable.status = 'PAID' AND receivable.open_amount = 0 AND receivable.payment_date IS NOT NULL AND receivable.payment_date <= :referenceDate THEN GREATEST(DATEDIFF(receivable.payment_date, receivable.due_date), 0) ELSE 0 END), 0) AS maximum_payment_delay_days,
                MAX(receivable.updated_at) AS last_receivable_update
            FROM receivables receivable
            INNER JOIN customer customer ON customer.id = receivable.customer_id AND customer.organization_id = :organizationId AND customer.deleted_at IS NULL
            WHERE receivable.organization_id = :organizationId
            {$customerFilter}
            GROUP BY receivable.customer_id
            SQL;
    }

    /** @param array<string, mixed> $row */
    private function hydrate(array $row): ReceivableFinancialAggregate
    {
        return new ReceivableFinancialAggregate(
            (int) $row['customer_id'],
            new DecimalAmount((string) $row['exposure']),
            new DecimalAmount((string) $row['overdue_exposure']),
            (int) $row['maximum_overdue_days'],
            (int) $row['receivables_count'],
            (int) $row['paid_count'],
            (int) $row['on_time_count'],
            (int) $row['late_count'],
            (int) $row['total_payment_delay_days'],
            (int) $row['maximum_payment_delay_days'],
            $this->dateTimeOrNull($row['last_receivable_update'] ?? null),
        );
    }

    private function dateTimeOrNull(mixed $value): ?\DateTimeImmutable
    {
        return null === $value || false === $value || '' === $value ? null : new \DateTimeImmutable((string) $value);
    }
}
