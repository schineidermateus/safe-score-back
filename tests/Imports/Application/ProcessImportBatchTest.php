<?php

declare(strict_types=1);

namespace App\Tests\Imports\Application;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Identity\Domain\Entity\User;
use App\Imports\Application\Processor\CreditLimitImportProcessor;
use App\Imports\Application\Processor\CustomerImportProcessor;
use App\Imports\Application\Processor\ImportProcessorRegistry;
use App\Imports\Application\Processor\ReceivableImportProcessor;
use App\Imports\Application\Service\CustomerImportResolver;
use App\Imports\Application\UseCase\ProcessImportBatch;
use App\Imports\Domain\Entity\ImportBatch;
use App\Imports\Domain\Entity\ImportRow;
use App\Imports\Domain\Enum\ImportAction;
use App\Imports\Domain\Enum\ImportBatchStatus;
use App\Imports\Domain\Enum\ImportRowStatus;
use App\Imports\Domain\Enum\ImportType;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Tests\Audit\Support\InMemoryAuditLogRepository;
use App\Tests\Credit\Support\InMemoryCreditLimitRepository;
use App\Tests\Customers\Support\InMemoryCustomerRepository;
use App\Tests\Imports\Support\InMemoryImportBatchRepository;
use App\Tests\Imports\Support\InMemoryImportRowRepository;
use App\Tests\Receivables\Support\InMemoryReceivableRepository;
use App\Tests\Support\CurrentContextStub;
use App\Tests\Support\EntityId;
use App\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class ProcessImportBatchTest extends TestCase
{
    public function testPartialSuccessIsIsolatedPerRowAndCountersRemainCoherent(): void
    {
        [$organization, $user, $context] = $this->context();
        $batches = new InMemoryImportBatchRepository();
        $rows = new InMemoryImportRowRepository();
        $customers = new InMemoryCustomerRepository();
        $limits = new InMemoryCreditLimitRepository();
        $receivables = new InMemoryReceivableRepository();
        $auditRepository = new InMemoryAuditLogRepository();
        $audit = new AuditLogger($auditRepository);
        $resolver = new CustomerImportResolver($customers);
        $processors = new ImportProcessorRegistry(new CustomerImportProcessor($customers, $resolver, $audit), new CreditLimitImportProcessor($limits, $customers, $audit), new ReceivableImportProcessor($receivables, $customers, $audit));
        $batch = $this->readyBatch($organization, $user);
        $batches->save($organization, $batch);
        $valid = ImportRow::create($batch, 2, ['external_id' => 'C-1'], new \DateTimeImmutable());
        $valid->markValid(['external_id' => 'C-1', 'legal_name' => 'Cliente', 'trade_name' => null, 'document' => '52998224725', 'status' => 'ACTIVE'], ImportAction::Create, new \DateTimeImmutable());
        $rows->save($organization, $valid);
        $conflict = ImportRow::create($batch, 3, ['external_id' => 'missing'], new \DateTimeImmutable());
        $conflict->markValid(['external_id' => 'missing', 'legal_name' => 'Ausente', 'trade_name' => null, 'document' => null, 'status' => 'ACTIVE'], ImportAction::Update, new \DateTimeImmutable());
        $rows->save($organization, $conflict);
        $useCase = new ProcessImportBatch($batches, $rows, $processors, $context, $context, new AuthorizationService($context), new ImmediateTransactionManager(), $audit);
        $output = $useCase->execute($batch->requireId())->toArray();
        self::assertSame(ImportBatchStatus::CompletedWithErrors->value, $output['status']);
        self::assertSame(1, $output['success_rows']);
        self::assertSame(1, $output['error_rows']);
        self::assertSame(ImportRowStatus::Processed, $rows->all()[0]->status());
        self::assertSame(ImportRowStatus::Failed, $rows->all()[1]->status());
        self::assertCount(2, $auditRepository->all());
    }

    public function testIdempotentCustomerProcessorSkipsWithoutSecondAudit(): void
    {
        [$organization, $user] = $this->context();
        $customers = new InMemoryCustomerRepository();
        $auditRepository = new InMemoryAuditLogRepository();
        $processor = new CustomerImportProcessor($customers, new CustomerImportResolver($customers), new AuditLogger($auditRepository));
        $data = ['external_id' => 'C-1', 'legal_name' => 'Cliente', 'trade_name' => null, 'document' => '52998224725', 'status' => 'ACTIVE'];
        self::assertFalse($processor->process($data, ImportAction::Create, $organization, $user)->skipped);
        self::assertTrue($processor->process($data, ImportAction::Create, $organization, $user)->skipped);
        self::assertCount(1, $auditRepository->all());
    }

    /** @return array{Organization, User, CurrentContextStub} */
    private function context(): array
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Org', null, null, $now);
        $user = User::create('User', 'analyst@imports.local', $now);
        EntityId::assign($organization, 1);
        EntityId::assign($user, 1);
        $membership = OrganizationMembership::join($organization, $user, MembershipRole::Analyst, $now);
        EntityId::assign($membership, 1);

        return [$organization, $user, new CurrentContextStub($user, $organization, $membership)];
    }

    private function readyBatch(Organization $organization, User $user): ImportBatch
    {
        $now = new \DateTimeImmutable();
        $batch = ImportBatch::create($organization, $user, ImportType::Customers, 'safe.csv', 'customers.csv', str_repeat('a', 48).'.csv', str_repeat('b', 64), 10, ['external_id', 'legal_name'], 'UTF-8', ',', $now);
        $batch->setMapping(['external_id' => 'external_id', 'legal_name' => 'legal_name'], $now);
        $batch->startValidation($now);
        $batch->finishValidation(2, 2, 0, $now);

        return $batch;
    }
}
