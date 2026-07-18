<?php

declare(strict_types=1);

namespace App\Tests\Reporting\Infrastructure;

use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\ValueObject\MoneyAmount;
use App\Credit\Infrastructure\Persistence\Doctrine\DoctrineCreditLimitRepository;
use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\ValueObject\ReceivableAmount;
use App\Reporting\Domain\ValueObject\ReferenceDate;
use App\Reporting\Infrastructure\Persistence\Doctrine\DoctrineFinancialIndicatorsQuery;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineFinancialIndicatorsQueryTest extends KernelTestCase
{
    public function testAggregatesAreExactReferenceDrivenAndTenantScoped(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $query = self::getContainer()->get(DoctrineFinancialIndicatorsQuery::class);
        $creditLimits = self::getContainer()->get(DoctrineCreditLimitRepository::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        self::assertInstanceOf(DoctrineFinancialIndicatorsQuery::class, $query);
        self::assertInstanceOf(DoctrineCreditLimitRepository::class, $creditLimits);
        $connection = $entityManager->getConnection();
        try {
            $tables = $connection->createSchemaManager()->listTableNames();
        } catch (\Throwable $exception) {
            self::markTestSkipped('MySQL de teste indisponível: '.$exception->getMessage());
        }
        if (!in_array('receivables', $tables, true) || !in_array('import_batches', $tables, true)) {
            self::markTestSkipped('Execute as migrations no banco MySQL de teste antes deste teste.');
        }

        $connection->beginTransaction();
        try {
            $now = new \DateTimeImmutable('2026-07-15 12:00:00');
            $organizationA = Organization::create('Indicators Integration A', null, null, $now);
            $organizationB = Organization::create('Indicators Integration B', null, null, $now);
            $customerA = Customer::create($organizationA, 'Customer A', null, null, null, null, null, $now);
            $customerB = Customer::create($organizationB, 'Customer B', null, null, null, null, null, $now);
            $user = User::create('User', 'indicators-integration@example.test', $now);
            foreach ([$organizationA, $organizationB, $customerA, $customerB, $user] as $entity) {
                $entityManager->persist($entity);
            }
            $entityManager->flush();
            $limit = CreditLimit::createActive($organizationA, $customerA, new MoneyAmount('500.00'), new \DateTimeImmutable('2026-01-01'), null, 'Integration', $user, $now);
            $upcoming = $this->receivable($organizationA, $customerA, 'UPCOMING', '2026-07-16', '100.00', $now);
            $dueToday = $this->receivable($organizationA, $customerA, 'TODAY', '2026-07-15', '70.00', $now);
            $partial = $this->receivable($organizationA, $customerA, 'PARTIAL', '2026-07-14', '50.00', $now);
            $partialPayment = $partial->registerPayment(new ReceivableAmount('20.00'), new \DateTimeImmutable('2026-07-10'), $user, $now);
            $paid = $this->receivable($organizationA, $customerA, 'PAID', '2026-07-01', '40.00', $now);
            $paidPayment = $paid->registerPayment(new ReceivableAmount('40.00'), new \DateTimeImmutable('2026-07-01'), $user, $now);
            $cancelled = $this->receivable($organizationA, $customerA, 'CANCELLED', '2026-07-01', '60.00', $now);
            $cancelled->cancel($user, 'Integration', $now);
            $otherTenant = $this->receivable($organizationB, $customerB, 'OTHER', '2026-07-01', '999.00', $now);
            foreach ([$limit, $upcoming, $dueToday, $partial, $partialPayment, $paid, $paidPayment, $cancelled, $otherTenant] as $entity) {
                $entityManager->persist($entity);
            }
            $entityManager->flush();
            $connection->executeStatement(
                "INSERT INTO receivables (organization_id, customer_id, source, external_id, document_number, issue_date, due_date, original_amount, open_amount, paid_amount, payment_date, status, cancelled_at, cancelled_by_user_id, cancellation_reason, created_at, updated_at) VALUES (:organizationId, :customerId, 'CORRUPTED', 'CROSS-TENANT', 'CROSS-TENANT', '2026-01-01', '2026-07-01', 9999.00, 9999.00, 0.00, NULL, 'OPEN', NULL, NULL, NULL, :createdAt, :updatedAt)",
                ['organizationId' => $organizationA->requireId(), 'customerId' => $customerB->requireId(), 'createdAt' => '2026-07-15 12:00:00', 'updatedAt' => '2026-07-15 12:00:00'],
            );
            $connection->executeStatement(
                "INSERT INTO credit_limits (organization_id, customer_id, approved_by_user_id, amount, valid_from, valid_until, status, reason, created_at, updated_at) VALUES (:organizationId, :customerId, :userId, 9999.00, '2026-01-01', NULL, 'ACTIVE', 'Cross tenant corrupted relation', :createdAt, :updatedAt)",
                ['organizationId' => $organizationA->requireId(), 'customerId' => $customerB->requireId(), 'userId' => $user->requireId(), 'createdAt' => '2026-07-15 12:00:00', 'updatedAt' => '2026-07-15 12:00:00'],
            );

            $aggregate = $query->aggregateForCustomer($organizationA, $customerA->requireId(), ReferenceDate::fromString('2026-07-15'));
            self::assertSame('200.00', (string) $aggregate->exposure);
            self::assertSame('30.00', (string) $aggregate->overdueExposure);
            self::assertSame(1, $aggregate->maximumOverdueDays);
            self::assertSame(1, $aggregate->paidReceivablesCount);
            self::assertSame(1, $aggregate->onTimePaidReceivablesCount);
            self::assertSame('200.00', (string) $query->totalExposureForOrganization($organizationA));
            self::assertNotNull($query->lastCreditLimitUpdateForCustomer($organizationA, $customerA->requireId()));
            self::assertCount(1, $creditLimits->findActiveByOrganizationAndDate($organizationA, new \DateTimeImmutable('2026-07-15')));
        } finally {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $entityManager->clear();
        }
    }

    private function receivable(Organization $organization, Customer $customer, string $externalId, string $dueDate, string $amount, \DateTimeImmutable $now): Receivable
    {
        return Receivable::create($organization, $customer, 'INDICATORS_TEST', $externalId, $externalId, new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable($dueDate), new ReceivableAmount($amount), $now);
    }
}
