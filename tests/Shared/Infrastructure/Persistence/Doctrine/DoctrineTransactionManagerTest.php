<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineTransactionManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class DoctrineTransactionManagerTest extends TestCase
{
    public function testItCommitsSuccessfulOperation(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('beginTransaction');
        $entityManager->expects(self::once())->method('flush');
        $entityManager->expects(self::once())->method('commit');
        $entityManager->expects(self::never())->method('rollback');

        $result = (new DoctrineTransactionManager($entityManager))->transactional(static fn (): string => 'ok');

        self::assertSame('ok', $result);
    }

    public function testItRollsBackFailedOperation(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('beginTransaction');
        $entityManager->expects(self::never())->method('flush');
        $entityManager->expects(self::never())->method('commit');
        $entityManager->expects(self::once())->method('rollback');

        $this->expectException(\RuntimeException::class);
        (new DoctrineTransactionManager($entityManager))->transactional(
            static fn (): never => throw new \RuntimeException('failure'),
        );
    }
}
