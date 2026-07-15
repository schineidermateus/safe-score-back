<?php

declare(strict_types=1);

namespace App\Tests\Credit\Infrastructure\Persistence;

use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\ValueObject\MoneyAmount;
use App\Credit\Infrastructure\Persistence\Doctrine\DoctrineCreditLimitRepository;
use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineCreditLimitRepositoryTest extends KernelTestCase
{
    public function testMySqlGeneratesIntegerIdAndColumnsAreCompatible(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(DoctrineCreditLimitRepository::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        self::assertInstanceOf(DoctrineCreditLimitRepository::class, $repository);
        $connection = $entityManager->getConnection();

        try {
            $tables = $connection->createSchemaManager()->listTableNames();
        } catch (\Throwable $exception) {
            self::markTestSkipped('MySQL de teste indisponível: '.$exception->getMessage());
        }
        if (!in_array('credit_limits', $tables, true)) {
            self::markTestSkipped('Execute as migrations no banco MySQL de teste antes deste teste.');
        }

        $connection->beginTransaction();
        try {
            $now = new \DateTimeImmutable();
            $organization = Organization::create('Integration', null, null, $now);
            $user = User::create('User', 'credit-integration@example.com', $now);
            $customer = Customer::create($organization, 'Customer', null, null, null, null, null, $now);
            $entityManager->persist($organization);
            $entityManager->persist($user);
            $entityManager->persist($customer);
            $entityManager->flush();
            $limit = CreditLimit::createActive($organization, $customer, new MoneyAmount('123.45'), new \DateTimeImmutable('2026-01-01'), null, 'reason', $user, $now);
            $repository->save($organization, $limit);

            self::assertIsInt($limit->id());
            self::assertSame($limit, $repository->findByIdAndOrganization($limit->requireId(), $organization));

            /** @var array{COLUMN_TYPE: string, EXTRA: string}|false $id */
            $id = $connection->fetchAssociative("SELECT COLUMN_TYPE, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'credit_limits' AND COLUMN_NAME = 'id'");
            self::assertIsArray($id);
            self::assertSame('int unsigned', $id['COLUMN_TYPE']);
            self::assertSame('auto_increment', $id['EXTRA']);

            /** @var array<string, string> $types */
            $types = $connection->fetchAllKeyValue("SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'credit_limits' AND COLUMN_NAME IN ('organization_id', 'customer_id', 'approved_by_user_id')");
            self::assertSame('int unsigned', $types['organization_id'] ?? null);
            self::assertSame('int unsigned', $types['customer_id'] ?? null);
            self::assertSame('int unsigned', $types['approved_by_user_id'] ?? null);
        } finally {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $entityManager->clear();
        }
    }
}
