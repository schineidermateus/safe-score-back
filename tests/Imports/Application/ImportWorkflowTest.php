<?php

declare(strict_types=1);

namespace App\Tests\Imports\Application;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Identity\Domain\Entity\User;
use App\Imports\Application\Normalization\DateNormalizer;
use App\Imports\Application\Normalization\DocumentNormalizer;
use App\Imports\Application\Normalization\MoneyNormalizer;
use App\Imports\Application\Normalization\TextNormalizer;
use App\Imports\Application\Processor\CreditLimitImportProcessor;
use App\Imports\Application\Processor\CustomerImportProcessor;
use App\Imports\Application\Processor\ImportProcessorRegistry;
use App\Imports\Application\Processor\ReceivableImportProcessor;
use App\Imports\Application\Schema\ImportSchemaRegistry;
use App\Imports\Application\Service\CustomerImportResolver;
use App\Imports\Application\UseCase\CreateImportBatch;
use App\Imports\Application\UseCase\GetImportBatch;
use App\Imports\Application\UseCase\GetImportPreview;
use App\Imports\Application\UseCase\ProcessImportBatch;
use App\Imports\Application\UseCase\SetImportMapping;
use App\Imports\Application\UseCase\ValidateImportBatch;
use App\Imports\Application\Validation\CreditLimitImportValidator;
use App\Imports\Application\Validation\CustomerImportValidator;
use App\Imports\Application\Validation\ImportRowValidatorRegistry;
use App\Imports\Application\Validation\ReceivableImportValidator;
use App\Imports\Domain\Enum\ImportAction;
use App\Imports\Domain\Enum\ImportBatchStatus;
use App\Imports\Infrastructure\Csv\NativeCsvReader;
use App\Imports\Infrastructure\Storage\LocalImportFileStorage;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Shared\Domain\Exception\DomainException;
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

final class ImportWorkflowTest extends TestCase
{
    private string $storageDirectory;

    protected function setUp(): void
    {
        $this->storageDirectory = sys_get_temp_dir().'/safescore-workflow-'.bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->storageDirectory)) {
            foreach (glob($this->storageDirectory.'/*') ?: [] as $file) {
                unlink($file);
            }
            rmdir($this->storageDirectory);
        }
    }

    public function testCompleteCustomerWorkflowAndDuplicateFileDetection(): void
    {
        [$organization, $user, $context] = $this->context(1, 'analyst@example.test');
        $batches = new InMemoryImportBatchRepository();
        $rows = new InMemoryImportRowRepository();
        $customers = new InMemoryCustomerRepository();
        $limits = new InMemoryCreditLimitRepository();
        $receivables = new InMemoryReceivableRepository();
        $auditRepository = new InMemoryAuditLogRepository();
        $audit = new AuditLogger($auditRepository);
        $authorization = new AuthorizationService($context);
        $transactions = new ImmediateTransactionManager();
        $storage = new LocalImportFileStorage($this->storageDirectory);
        $csv = new NativeCsvReader();
        $schemas = new ImportSchemaRegistry();
        $resolver = new CustomerImportResolver($customers);
        $validators = new ImportRowValidatorRegistry(
            new CustomerImportValidator(new TextNormalizer(), new DocumentNormalizer(), $resolver),
            new CreditLimitImportValidator(new TextNormalizer(), new DocumentNormalizer(), new MoneyNormalizer(), new DateNormalizer(), $resolver, $limits),
            new ReceivableImportValidator(new TextNormalizer(), new DocumentNormalizer(), new MoneyNormalizer(), new DateNormalizer(), $resolver, $receivables),
        );
        $processors = new ImportProcessorRegistry(
            new CustomerImportProcessor($customers, $resolver, $audit),
            new CreditLimitImportProcessor($limits, $customers, $audit),
            new ReceivableImportProcessor($receivables, $customers, $audit),
        );
        $create = new CreateImportBatch($batches, $storage, $csv, $context, $context, $authorization, $audit, $transactions);
        $created = $create->execute('CUSTOMERS', __DIR__.'/../Fixtures/customers-valid.csv', 'customers.csv')->toArray();
        $batchId = $created['id'];
        self::assertIsInt($batchId);
        self::assertArrayNotHasKey('storage_key', $created);
        $mapping = ['external_id' => 'external_id', 'legal_name' => 'legal_name', 'trade_name' => 'trade_name', 'document' => 'document', 'status' => 'status'];
        (new SetImportMapping($batches, $rows, $schemas, $context, $context, $authorization, $audit, $transactions))->execute($batchId, $mapping);
        $validated = (new ValidateImportBatch($batches, $rows, $storage, $csv, $schemas, $validators, $context, $context, $authorization, $audit, $transactions))->execute($batchId)->toArray();
        self::assertSame(ImportBatchStatus::Ready->value, $validated['status']);
        $preview = (new GetImportPreview($batches, $rows, $context, $authorization))->execute($batchId, 1, 100);
        self::assertSame(ImportAction::Create->value, $preview['items'][0]['action']);
        $processed = (new ProcessImportBatch($batches, $rows, $processors, $context, $context, $authorization, $transactions, $audit))->execute($batchId)->toArray();
        self::assertSame(ImportBatchStatus::Completed->value, $processed['status']);
        self::assertSame(1, $processed['success_rows']);
        self::assertCount(1, $customers->list($organization, null, null, 1, 10, 'legal_name'));
        self::assertCount(6, $auditRepository->all());

        try {
            $create->execute('CUSTOMERS', __DIR__.'/../Fixtures/customers-duplicate.csv', 'renamed.csv');
            self::fail('Duplicate content must be rejected.');
        } catch (DomainException $exception) {
            self::assertSame('IMPORT_DUPLICATE_FILE', $exception->errorCode());
        }
        self::assertCount(1, glob($this->storageDirectory.'/*.csv') ?: []);

        [, , $otherContext] = $this->context(2, 'other@example.test');
        $otherCreate = new CreateImportBatch(
            $batches,
            $storage,
            $csv,
            $otherContext,
            $otherContext,
            new AuthorizationService($otherContext),
            $audit,
            $transactions,
        );
        $otherBatch = $otherCreate->execute(
            'CUSTOMERS',
            __DIR__.'/../Fixtures/customers-valid.csv',
            'customers-other-organization.csv',
        )->toArray();
        self::assertNotSame($batchId, $otherBatch['id']);

        $this->expectException(DomainException::class);
        (new GetImportBatch($batches, $otherContext, new AuthorizationService($otherContext)))->execute($batchId);
    }

    /** @return array{Organization, User, CurrentContextStub} */
    private function context(int $id, string $email): array
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization '.$id, null, null, $now);
        $user = User::create('User '.$id, $email, $now);
        EntityId::assign($organization, $id);
        EntityId::assign($user, $id);
        $membership = OrganizationMembership::join($organization, $user, MembershipRole::Analyst, $now);
        EntityId::assign($membership, $id);

        return [$organization, $user, new CurrentContextStub($user, $organization, $membership)];
    }
}
