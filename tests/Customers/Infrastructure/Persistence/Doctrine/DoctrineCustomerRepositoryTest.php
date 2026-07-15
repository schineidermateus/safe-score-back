<?php

declare(strict_types=1);

namespace App\Tests\Customers\Infrastructure\Persistence\Doctrine;

use App\Customers\Domain\Entity\Customer;
use App\Customers\Infrastructure\Persistence\Doctrine\DoctrineCustomerRepository;
use App\Organizations\Domain\Entity\Organization;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class DoctrineCustomerRepositoryTest extends KernelTestCase
{
    public function testMySqlGeneratesIntegerIdsAndRepositoryEnforcesTenant(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $repository = self::getContainer()->get(DoctrineCustomerRepository::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        self::assertInstanceOf(DoctrineCustomerRepository::class, $repository);
        $connection = $entityManager->getConnection();

        try {
            $tables = $connection->createSchemaManager()->listTableNames();
        } catch (\Throwable $exception) {
            self::markTestSkipped('MySQL de teste indisponível: '.$exception->getMessage());
        }

        if (!in_array('customer', $tables, true) || !in_array('organization', $tables, true)) {
            self::markTestSkipped('Execute as migrations no banco MySQL de teste antes deste teste.');
        }

        $connection->beginTransaction();

        try {
            $now = new \DateTimeImmutable();
            $organizationA = Organization::create('Integração A', null, null, $now);
            $organizationB = Organization::create('Integração B', null, null, $now);
            $entityManager->persist($organizationA);
            $entityManager->persist($organizationB);
            $entityManager->flush();

            $customerA = $this->customer($organizationA, 'Cliente A', $now);
            $customerB = $this->customer($organizationB, 'Cliente B', $now);
            $repository->save($organizationA, $customerA);
            $repository->save($organizationB, $customerB);

            self::assertIsInt($customerA->id());
            self::assertIsInt($customerB->id());
            self::assertGreaterThan($customerA->requireId(), $customerB->requireId());
            self::assertSame($customerA, $repository->findById($organizationA, $customerA->requireId()));
            self::assertNull($repository->findById($organizationB, $customerA->requireId()));

            /** @var array{COLUMN_TYPE: string, IS_NULLABLE: string, EXTRA: string}|false $idColumn */
            $idColumn = $connection->fetchAssociative(
                <<<'SQL'
                    SELECT COLUMN_TYPE, IS_NULLABLE, EXTRA
                    FROM information_schema.COLUMNS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'customer'
                      AND COLUMN_NAME = 'id'
                    SQL,
            );
            self::assertIsArray($idColumn);
            self::assertSame('int unsigned', $idColumn['COLUMN_TYPE']);
            self::assertSame('NO', $idColumn['IS_NULLABLE']);
            self::assertSame('auto_increment', $idColumn['EXTRA']);

            /** @var array{CUSTOMER_TYPE: string, ORGANIZATION_TYPE: string, IS_NULLABLE: string}|false $foreignKeyTypes */
            $foreignKeyTypes = $connection->fetchAssociative(
                <<<'SQL'
                    SELECT customer_column.COLUMN_TYPE AS CUSTOMER_TYPE,
                           organization_column.COLUMN_TYPE AS ORGANIZATION_TYPE,
                           customer_column.IS_NULLABLE
                    FROM information_schema.COLUMNS customer_column
                    INNER JOIN information_schema.COLUMNS organization_column
                        ON organization_column.TABLE_SCHEMA = customer_column.TABLE_SCHEMA
                       AND organization_column.TABLE_NAME = 'organization'
                       AND organization_column.COLUMN_NAME = 'id'
                    WHERE customer_column.TABLE_SCHEMA = DATABASE()
                      AND customer_column.TABLE_NAME = 'customer'
                      AND customer_column.COLUMN_NAME = 'organization_id'
                    SQL,
            );
            self::assertIsArray($foreignKeyTypes);
            self::assertSame('int unsigned', $foreignKeyTypes['CUSTOMER_TYPE']);
            self::assertSame($foreignKeyTypes['ORGANIZATION_TYPE'], $foreignKeyTypes['CUSTOMER_TYPE']);
            self::assertSame('NO', $foreignKeyTypes['IS_NULLABLE']);

            /** @var array{REFERENCED_TABLE_NAME: string, REFERENCED_COLUMN_NAME: string}|false $foreignKey */
            $foreignKey = $connection->fetchAssociative(
                <<<'SQL'
                    SELECT REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                    FROM information_schema.KEY_COLUMN_USAGE
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'customer'
                      AND COLUMN_NAME = 'organization_id'
                      AND REFERENCED_TABLE_NAME IS NOT NULL
                    SQL,
            );
            self::assertIsArray($foreignKey);
            self::assertSame('organization', $foreignKey['REFERENCED_TABLE_NAME']);
            self::assertSame('id', $foreignKey['REFERENCED_COLUMN_NAME']);

            $uniqueColumns = $connection->fetchOne(
                <<<'SQL'
                    SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX)
                    FROM information_schema.STATISTICS
                    WHERE TABLE_SCHEMA = DATABASE()
                      AND TABLE_NAME = 'customer'
                      AND INDEX_NAME = 'uniq_customer_organization_document'
                      AND NON_UNIQUE = 0
                    GROUP BY INDEX_NAME
                    SQL,
            );
            self::assertSame('organization_id,document', $uniqueColumns);
            self::assertSame(
                0,
                (int) $connection->fetchOne('SELECT COUNT(*) FROM customer WHERE organization_id IS NULL'),
            );
        } finally {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            $entityManager->clear();
        }
    }

    private function customer(
        Organization $organization,
        string $legalName,
        \DateTimeImmutable $now,
    ): Customer {
        return Customer::create(
            $organization,
            $legalName,
            null,
            '04252011000110',
            null,
            null,
            null,
            $now,
        );
    }
}
