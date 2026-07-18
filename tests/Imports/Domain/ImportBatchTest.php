<?php

declare(strict_types=1);

namespace App\Tests\Imports\Domain;

use App\Identity\Domain\Entity\User;
use App\Imports\Domain\Entity\ImportBatch;
use App\Imports\Domain\Enum\ImportBatchStatus;
use App\Imports\Domain\Enum\ImportType;
use App\Organizations\Domain\Entity\Organization;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Support\EntityId;
use PHPUnit\Framework\TestCase;

final class ImportBatchTest extends TestCase
{
    public function testHappyPathAndCounters(): void
    {
        $batch = $this->batch();
        $now = new \DateTimeImmutable();
        $batch->setMapping(['Nome' => 'legal_name', 'Documento' => 'document'], $now);
        $batch->startValidation($now);
        $batch->finishValidation(3, 2, 1, $now);
        self::assertSame(ImportBatchStatus::Ready, $batch->status());
        $batch->startProcessing($now);
        $batch->finishProcessing(1, 1, 0, $now);
        self::assertSame(ImportBatchStatus::CompletedWithErrors, $batch->status());
        self::assertSame(1, $batch->successRows());
        self::assertSame(1, $batch->skippedRows());
        self::assertSame(1, $batch->errorRows());
    }

    public function testCompletedBatchCannotBeProcessedAgain(): void
    {
        $batch = $this->batch();
        $now = new \DateTimeImmutable();
        $batch->setMapping(['Nome' => 'legal_name'], $now);
        $batch->startValidation($now);
        $batch->finishValidation(1, 1, 0, $now);
        $batch->startProcessing($now);
        $batch->finishProcessing(1, 0, 0, $now);
        $this->expectException(DomainException::class);
        $batch->startProcessing($now);
    }

    public function testCancelledBatchCannotBeProcessed(): void
    {
        $batch = $this->batch();
        $batch->cancel(new \DateTimeImmutable());
        $this->expectException(DomainException::class);
        $batch->startProcessing(new \DateTimeImmutable());
    }

    public function testChangingMappingAfterValidationClearsCountersAndInvalidatesPreview(): void
    {
        $batch = $this->batch();
        $now = new \DateTimeImmutable();
        $batch->setMapping(['Nome' => 'legal_name', 'Documento' => 'document'], $now);
        $batch->startValidation($now);
        $batch->finishValidation(2, 1, 1, $now);
        $batch->assertPreviewAvailable();
        $batch->setMapping(['Nome' => 'legal_name', 'Documento' => 'document'], $now);
        self::assertSame(0, $batch->totalRows());
        self::assertSame(0, $batch->validRows());
        self::assertSame(0, $batch->errorRows());
        $this->expectException(DomainException::class);
        $batch->assertPreviewAvailable();
    }

    public function testProcessingFailurePreservesCommittedCounters(): void
    {
        $batch = $this->batch();
        $now = new \DateTimeImmutable();
        $batch->setMapping(['Nome' => 'legal_name', 'Documento' => 'document'], $now);
        $batch->startValidation($now);
        $batch->finishValidation(4, 3, 1, $now);
        $batch->startProcessing($now);
        $batch->failProcessing(1, 1, 1, 'IMPORT_PROCESSING_FAILED', $now);
        self::assertSame(ImportBatchStatus::Failed, $batch->status());
        self::assertSame(1, $batch->successRows());
        self::assertSame(1, $batch->skippedRows());
        self::assertSame(2, $batch->errorRows());
    }

    private function batch(): ImportBatch
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Org', null, null, $now);
        $user = User::create('User', 'imports@example.com', $now);
        EntityId::assign($organization, 1);
        EntityId::assign($user, 1);
        $batch = ImportBatch::create($organization, $user, ImportType::Customers, 'internal.csv', 'original.csv', str_repeat('a', 48).'.csv', str_repeat('b', 64), 10, ['Nome', 'Documento'], 'UTF-8', ',', $now);
        EntityId::assign($batch, 1);

        return $batch;
    }
}
