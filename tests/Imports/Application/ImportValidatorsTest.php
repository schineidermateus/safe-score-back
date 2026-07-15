<?php

declare(strict_types=1);

namespace App\Tests\Imports\Application;

use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\ValueObject\MoneyAmount;
use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Imports\Application\Normalization\DateNormalizer;
use App\Imports\Application\Normalization\DocumentNormalizer;
use App\Imports\Application\Normalization\MoneyNormalizer;
use App\Imports\Application\Normalization\TextNormalizer;
use App\Imports\Application\Service\CustomerImportResolver;
use App\Imports\Application\Validation\CreditLimitImportValidator;
use App\Imports\Application\Validation\CustomerImportValidator;
use App\Imports\Application\Validation\ReceivableImportValidator;
use App\Imports\Domain\Enum\ImportAction;
use App\Organizations\Domain\Entity\Organization;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\ValueObject\ReceivableAmount;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Credit\Support\InMemoryCreditLimitRepository;
use App\Tests\Customers\Support\InMemoryCustomerRepository;
use App\Tests\Receivables\Support\InMemoryReceivableRepository;
use App\Tests\Support\EntityId;
use PHPUnit\Framework\TestCase;

final class ImportValidatorsTest extends TestCase
{
    private Organization $organization;
    private Organization $otherOrganization;
    private User $user;
    private InMemoryCustomerRepository $customers;
    private InMemoryCreditLimitRepository $limits;
    private InMemoryReceivableRepository $receivables;

    protected function setUp(): void
    {
        $now = new \DateTimeImmutable();
        $this->organization = Organization::create('A', null, null, $now);
        $this->otherOrganization = Organization::create('B', null, null, $now);
        $this->user = User::create('User', 'imports@test.local', $now);
        EntityId::assign($this->organization, 1);
        EntityId::assign($this->otherOrganization, 2);
        EntityId::assign($this->user, 1);
        $this->customers = new InMemoryCustomerRepository();
        $this->limits = new InMemoryCreditLimitRepository();
        $this->receivables = new InMemoryReceivableRepository();
    }

    public function testCustomerCreateUpdateAndSkipArePredicted(): void
    {
        $validator = $this->customerValidator();
        $data = ['external_id' => 'C-1', 'legal_name' => 'Cliente', 'trade_name' => null, 'document' => '52998224725', 'status' => 'ACTIVE'];
        self::assertSame(ImportAction::Create, $validator->validate($data, $this->organization)->action);
        $customer = $this->customer('Cliente', 'C-1', '52998224725');
        $this->customers->save($this->organization, $customer);
        self::assertSame(ImportAction::Skip, $validator->validate($data, $this->organization)->action);
        $data['legal_name'] = 'Cliente Atualizado';
        self::assertSame(ImportAction::Update, $validator->validate($data, $this->organization)->action);
    }

    public function testCustomerLookupNeverCrossesTenant(): void
    {
        $customer = Customer::create($this->otherOrganization, 'Outro', null, '52998224725', 'C-OTHER', null, null, new \DateTimeImmutable());
        $this->customers->save($this->otherOrganization, $customer);
        $result = $this->customerValidator()->validate(['external_id' => 'C-OTHER', 'legal_name' => 'Novo', 'document' => null, 'status' => 'ACTIVE'], $this->organization);
        self::assertSame(ImportAction::Create, $result->action);
    }

    public function testArchivedCustomerIsReportedBeforeProcessing(): void
    {
        $customer = $this->customer('Archived', 'C-ARCHIVED', '52998224725');
        $this->customers->save($this->organization, $customer);
        $customer->delete(new \DateTimeImmutable());
        $this->customers->save($this->organization, $customer);

        try {
            $this->customerValidator()->validate(['external_id' => 'C-ARCHIVED', 'legal_name' => 'Archived', 'document' => '52998224725', 'status' => 'ACTIVE'], $this->organization);
            self::fail('Archived customer must not be recreated by an import.');
        } catch (DomainException $exception) {
            self::assertSame('IMPORT_CUSTOMER_ARCHIVED_OR_CONFLICT', $exception->errorCode());
        }
    }

    public function testCreditLimitIdenticalIsSkippedAndConflictErrors(): void
    {
        $customer = $this->customer('Cliente', 'C-1', '52998224725');
        $this->customers->save($this->organization, $customer);
        $now = new \DateTimeImmutable();
        $limit = CreditLimit::createActive($this->organization, $customer, new MoneyAmount('1000.00'), new \DateTimeImmutable('2026-07-01'), new \DateTimeImmutable('2026-12-31'), 'Importação', $this->user, $now);
        $this->limits->save($this->organization, $limit);
        $validator = $this->creditValidator();
        $data = ['customer_external_id' => 'C-1', 'amount' => '1.000,00', 'valid_from' => '01/07/2026', 'valid_until' => '31/12/2026', 'status' => 'ACTIVE', 'reason' => 'Importação'];
        self::assertSame(ImportAction::Skip, $validator->validate($data, $this->organization)->action);
        $data['amount'] = '1200,00';
        $this->expectException(\RuntimeException::class);
        $validator->validate($data, $this->organization);
    }

    public function testReceivableCreateAndExistingUpdateArePredicted(): void
    {
        $customer = $this->customer('Cliente', 'C-1', '52998224725');
        $this->customers->save($this->organization, $customer);
        $validator = $this->receivableValidator();
        $data = $this->receivableData();
        self::assertSame(ImportAction::Create, $validator->validate($data, $this->organization)->action);
        $receivable = Receivable::create($this->organization, $customer, 'ERP', 'R-1', 'NF-1', new \DateTimeImmutable('2026-07-01'), new \DateTimeImmutable('2026-07-31'), new ReceivableAmount('1000.00'), new \DateTimeImmutable());
        $this->receivables->save($this->organization, $receivable);
        self::assertSame(ImportAction::Skip, $validator->validate($data, $this->organization)->action);
        $data['document_number'] = 'NF-2';
        self::assertSame(ImportAction::Update, $validator->validate($data, $this->organization)->action);
    }

    public function testReceivableWithPaymentCannotBeOverwritten(): void
    {
        $customer = $this->customer('Cliente', 'C-1', '52998224725');
        $this->customers->save($this->organization, $customer);
        $receivable = Receivable::create($this->organization, $customer, 'ERP', 'R-1', 'NF-1', new \DateTimeImmutable('2026-07-01'), new \DateTimeImmutable('2026-07-31'), new ReceivableAmount('1000.00'), new \DateTimeImmutable());
        $receivable->registerPayment(new ReceivableAmount('100.00'), new \DateTimeImmutable('2026-07-10'), $this->user, new \DateTimeImmutable());
        $this->receivables->save($this->organization, $receivable);
        $this->expectException(\InvalidArgumentException::class);
        $this->receivableValidator()->validate($this->receivableData(), $this->organization);
    }

    public function testImportedReceivableCannotContainAggregatedPayment(): void
    {
        $customer = $this->customer('Cliente', 'C-1', '52998224725');
        $this->customers->save($this->organization, $customer);
        $data = $this->receivableData();
        $data['paid_amount'] = '100,00';
        $data['open_amount'] = '900,00';
        $this->expectException(\InvalidArgumentException::class);
        $this->receivableValidator()->validate($data, $this->organization);
    }

    private function customerValidator(): CustomerImportValidator
    {
        return new CustomerImportValidator(new TextNormalizer(), new DocumentNormalizer(), new CustomerImportResolver($this->customers));
    }

    private function creditValidator(): CreditLimitImportValidator
    {
        return new CreditLimitImportValidator(new TextNormalizer(), new DocumentNormalizer(), new MoneyNormalizer(), new DateNormalizer(), new CustomerImportResolver($this->customers), $this->limits);
    }

    private function receivableValidator(): ReceivableImportValidator
    {
        return new ReceivableImportValidator(new TextNormalizer(), new DocumentNormalizer(), new MoneyNormalizer(), new DateNormalizer(), new CustomerImportResolver($this->customers), $this->receivables);
    }

    private function customer(string $name, string $external, string $document): Customer
    {
        return Customer::create($this->organization, $name, null, $document, $external, null, null, new \DateTimeImmutable());
    }

    /** @return array<string, string|null> */
    private function receivableData(): array
    {
        return ['customer_external_id' => 'C-1', 'customer_document' => null, 'source' => 'ERP', 'external_id' => 'R-1', 'document_number' => 'NF-1', 'issue_date' => '2026-07-01', 'due_date' => '2026-07-31', 'original_amount' => '1000.00', 'open_amount' => '1000.00', 'paid_amount' => '0.00', 'payment_date' => null, 'status' => 'OPEN'];
    }
}
