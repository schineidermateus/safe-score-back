<?php

declare(strict_types=1);

namespace App\Tests\Receivables\Infrastructure\Persistence;

use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\ValueObject\ReceivableAmount;
use App\Receivables\Infrastructure\Persistence\Doctrine\DoctrineReceivablePaymentRepository;
use App\Receivables\Infrastructure\Persistence\Doctrine\DoctrineReceivableRepository;
use App\Shared\Domain\Exception\DomainException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineReceivableRepositoryTest extends KernelTestCase
{
    public function testMySqlIdentifiersForeignKeysTenantAndIdempotency(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $receivables = self::getContainer()->get(DoctrineReceivableRepository::class);
        $payments = self::getContainer()->get(DoctrineReceivablePaymentRepository::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        self::assertInstanceOf(DoctrineReceivableRepository::class, $receivables);
        self::assertInstanceOf(DoctrineReceivablePaymentRepository::class, $payments);
        $connection = $entityManager->getConnection();

        try {
            $tables = $connection->createSchemaManager()->listTableNames();
        } catch (\Throwable $exception) {
            self::markTestSkipped('MySQL de teste indisponível: '.$exception->getMessage());
        }
        if (!in_array('receivables', $tables, true) || !in_array('receivable_payments', $tables, true)) {
            self::markTestSkipped('Execute as migrations no banco MySQL de teste antes deste teste.');
        }

        $connection->beginTransaction();
        try {
            $now = new \DateTimeImmutable();
            $organizationA = Organization::create('Integration A', null, null, $now);
            $organizationB = Organization::create('Integration B', null, null, $now);
            $customerA = Customer::create($organizationA, 'Customer A', null, null, null, null, null, $now);
            $customerB = Customer::create($organizationB, 'Customer B', null, null, null, null, null, $now);
            $user = User::create('User', 'receivable-integration@example.com', $now);
            foreach ([$organizationA, $organizationB, $customerA, $customerB, $user] as $entity) {
                $entityManager->persist($entity);
            }
            $entityManager->flush();
            $receivableA = Receivable::create($organizationA, $customerA, 'ERP', 'SHARED', 'A-1', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-02-01'), new ReceivableAmount('100.00'), $now);
            $receivableB = Receivable::create($organizationB, $customerB, 'ERP', 'SHARED', 'B-1', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-02-01'), new ReceivableAmount('100.00'), $now);
            $receivables->save($organizationA, $receivableA);
            $receivables->save($organizationB, $receivableB);
            $payment = $receivableA->registerPayment(new ReceivableAmount('25.50'), new \DateTimeImmutable('2026-01-15'), $user, $now);
            $receivables->save($organizationA, $receivableA);
            $payments->save($organizationA, $payment);

            self::assertIsInt($receivableA->id());
            self::assertIsInt($payment->id());
            self::assertNull($receivables->findByIdAndOrganization($receivableA->requireId(), $organizationB));
            self::assertCount(1, $payments->listByReceivableAndOrganization($receivableA, $organizationA));
            try {
                $duplicate = Receivable::create($organizationA, $customerA, 'ERP', 'SHARED', 'A-2', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-02-01'), new ReceivableAmount('1.00'), $now);
                $receivables->save($organizationA, $duplicate);
                self::fail('Duplicate external key must conflict.');
            } catch (DomainException $exception) {
                self::assertSame('RECEIVABLE_DUPLICATE_EXTERNAL_KEY', $exception->errorCode());
                self::assertSame(409, $exception->statusCode());
            }

            /** @var array<string, string> $idTypes */
            $idTypes = $connection->fetchAllKeyValue("SELECT CONCAT(TABLE_NAME, '.', COLUMN_NAME), COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND ((TABLE_NAME = 'receivables' AND COLUMN_NAME IN ('id', 'organization_id', 'customer_id', 'cancelled_by_user_id')) OR (TABLE_NAME = 'receivable_payments' AND COLUMN_NAME IN ('id', 'organization_id', 'receivable_id', 'created_by_user_id')))");
            foreach ($idTypes as $type) {
                self::assertSame('int unsigned', $type);
            }
            /** @var array<string, string> $moneyTypes */
            $moneyTypes = $connection->fetchAllKeyValue("SELECT CONCAT(TABLE_NAME, '.', COLUMN_NAME), COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND ((TABLE_NAME = 'receivables' AND COLUMN_NAME IN ('original_amount', 'open_amount', 'paid_amount')) OR (TABLE_NAME = 'receivable_payments' AND COLUMN_NAME = 'amount'))");
            foreach ($moneyTypes as $type) {
                self::assertSame('decimal(19,2)', $type);
            }
        } finally {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $entityManager->clear();
        }
    }
}
