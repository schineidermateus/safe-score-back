<?php

declare(strict_types=1);

namespace App\Tests\Receivables\Application;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Receivables\Application\DTO\CancelReceivableInput;
use App\Receivables\Application\DTO\CreateReceivableInput;
use App\Receivables\Application\DTO\ListReceivablesInput;
use App\Receivables\Application\DTO\RegisterReceivablePaymentInput;
use App\Receivables\Application\DTO\UpdateReceivableInput;
use App\Receivables\Application\Service\ReceivableOutputFactory;
use App\Receivables\Application\Service\ReceivableStatusResolver;
use App\Receivables\Application\UseCase\CancelReceivable;
use App\Receivables\Application\UseCase\CreateReceivable;
use App\Receivables\Application\UseCase\GetReceivable;
use App\Receivables\Application\UseCase\ListReceivables;
use App\Receivables\Application\UseCase\RegisterReceivablePayment;
use App\Receivables\Application\UseCase\UpdateReceivable;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Service\AgingClassifier;
use App\Receivables\Domain\ValueObject\ReceivableAmount;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Audit\Support\InMemoryAuditLogRepository;
use App\Tests\Customers\Support\InMemoryCustomerRepository;
use App\Tests\Receivables\Support\InMemoryReceivablePaymentRepository;
use App\Tests\Receivables\Support\InMemoryReceivableRepository;
use App\Tests\Support\CurrentContextStub;
use App\Tests\Support\EntityId;
use App\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class ReceivableUseCasesTest extends TestCase
{
    public function testCreateUsesTenantWritesStringMoneyAndAudit(): void
    {
        [$organization, $context, $customer, $customers] = $this->context('A', 1);
        $receivables = new InMemoryReceivableRepository();
        $audits = new InMemoryAuditLogRepository();

        $output = $this->creator($receivables, $customers, $context, $audits)->execute(
            new CreateReceivableInput($customer->requireId(), 'NF-100', '2026-07-01', '2026-08-01', '1000.10', 'ERP-100'),
        );

        self::assertSame($organization, $receivables->all()[0]->organization());
        self::assertSame('1000.10', $output->originalAmount);
        self::assertSame('1000.10', $output->openAmount);
        self::assertSame('0.00', $output->paidAmount);
        self::assertSame('MANUAL', $output->source);
        self::assertArrayNotHasKey('organization_id', $output->toArray());
        self::assertSame('RECEIVABLE_CREATED', $audits->all()[0]->action());
        self::assertSame($organization, $audits->all()[0]->organization());
        self::assertSame($context->currentUser(), $audits->all()[0]->user());
        self::assertSame('1000.10', $audits->all()[0]->afterData()['original_amount'] ?? null);
    }

    public function testIdempotencyIsScopedByTenantSourceAndNullableExternalId(): void
    {
        [$organizationA, $contextA, $customerA, $customers] = $this->context('A', 1);
        [$organizationB, $contextB, $customerB] = $this->context('B', 2, MembershipRole::Owner, $customers);
        $receivables = new InMemoryReceivableRepository();
        $audits = new InMemoryAuditLogRepository();
        $inputA = new CreateReceivableInput($customerA->requireId(), 'A-1', '2026-01-01', '2026-02-01', '10.00', 'SHARED');
        $this->creator($receivables, $customers, $contextA, $audits)->execute($inputA);

        try {
            $this->creator($receivables, $customers, $contextA, $audits)->execute($inputA);
            self::fail('Same tenant/source/external id must conflict.');
        } catch (DomainException $exception) {
            self::assertSame('RECEIVABLE_DUPLICATE_EXTERNAL_KEY', $exception->errorCode());
            self::assertSame(409, $exception->statusCode());
        }

        $this->creator($receivables, $customers, $contextB, $audits)->execute(new CreateReceivableInput($customerB->requireId(), 'B-1', '2026-01-01', '2026-02-01', '10.00', 'SHARED'));
        $this->creator($receivables, $customers, $contextA, $audits)->execute(new CreateReceivableInput($customerA->requireId(), 'NULL-1', '2026-01-01', '2026-02-01', '10.00'));
        $this->creator($receivables, $customers, $contextA, $audits)->execute(new CreateReceivableInput($customerA->requireId(), 'NULL-2', '2026-01-01', '2026-02-01', '10.00'));
        $differentSource = Receivable::create($organizationA, $customerA, 'ERP', 'SHARED', 'ERP-1', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-02-01'), new ReceivableAmount('10.00'), new \DateTimeImmutable());
        $receivables->save($organizationA, $differentSource);

        self::assertCount(5, $receivables->all());
        self::assertSame($organizationB, $receivables->all()[1]->organization());
    }

    public function testPaymentsUpdateBalancesPreserveHistoryAndAudit(): void
    {
        [, $context, $customer, $customers] = $this->context('A', 1);
        $receivables = new InMemoryReceivableRepository();
        $payments = new InMemoryReceivablePaymentRepository();
        $audits = new InMemoryAuditLogRepository();
        $created = $this->creator($receivables, $customers, $context, $audits)->execute(new CreateReceivableInput($customer->requireId(), 'NF-1', '2026-01-01', '2026-12-01', '100.00'));
        $useCase = new RegisterReceivablePayment($receivables, $payments, $context, $context, new AuthorizationService($context), new ImmediateTransactionManager(), new AuditLogger($audits), $this->outputFactory());

        $partial = $useCase->execute($created->id, new RegisterReceivablePaymentInput('40.00', '2026-01-15'));
        $paid = $useCase->execute($created->id, new RegisterReceivablePaymentInput('60.00', '2026-12-02'));

        self::assertSame('60.00', $partial->openAmount);
        self::assertSame('40.00', $partial->paidAmount);
        self::assertSame('PARTIALLY_PAID', $partial->status);
        self::assertSame('0.00', $paid->openAmount);
        self::assertSame('100.00', $paid->paidAmount);
        self::assertSame('PAID', $paid->status);
        self::assertSame('2026-12-02', $paid->paymentDate);
        self::assertCount(2, $payments->all());
        self::assertSame(['40.00', '60.00'], array_map(static fn ($payment): string => $payment->amount(), $payments->all()));
        self::assertSame('RECEIVABLE_PAYMENT_REGISTERED', $audits->all()[2]->action());
        self::assertSame('40.00', $audits->all()[1]->metadata()['amount'] ?? null);
        self::assertSame('100.00', $audits->all()[2]->afterData()['paid_amount'] ?? null);
    }

    public function testInvalidPaymentDateOverpaymentAndCancelledPaymentReturnStableErrors(): void
    {
        [, $context, $customer, $customers] = $this->context('A', 1);
        $receivables = new InMemoryReceivableRepository();
        $payments = new InMemoryReceivablePaymentRepository();
        $audits = new InMemoryAuditLogRepository();
        $created = $this->creator($receivables, $customers, $context, $audits)->execute(new CreateReceivableInput($customer->requireId(), 'NF-1', '2026-01-10', '2026-02-01', '100.00'));
        $payment = new RegisterReceivablePayment($receivables, $payments, $context, $context, new AuthorizationService($context), new ImmediateTransactionManager(), new AuditLogger($audits), $this->outputFactory());

        $this->assertDomainError(static fn () => $payment->execute($created->id, new RegisterReceivablePaymentInput('1.00', '2026-01-09')), 'RECEIVABLE_INVALID_DATES', 422);
        $this->assertDomainError(static fn () => $payment->execute($created->id, new RegisterReceivablePaymentInput('100.01', '2026-01-10')), 'RECEIVABLE_PAYMENT_EXCEEDS_BALANCE', 409);
        (new CancelReceivable($receivables, $context, $context, new AuthorizationService($context), new ImmediateTransactionManager(), new AuditLogger($audits), $this->outputFactory()))->execute($created->id, new CancelReceivableInput('Duplicado'));
        $this->assertDomainError(static fn () => $payment->execute($created->id, new RegisterReceivablePaymentInput('1.00', '2026-01-10')), 'RECEIVABLE_PAYMENT_NOT_ALLOWED', 409);
        self::assertCount(0, $payments->all());
    }

    public function testUpdateAndCancelHaveBeforeAfterUserAndReason(): void
    {
        [, $context, $customer, $customers] = $this->context('A', 1);
        $receivables = new InMemoryReceivableRepository();
        $audits = new InMemoryAuditLogRepository();
        $created = $this->creator($receivables, $customers, $context, $audits)->execute(new CreateReceivableInput($customer->requireId(), 'OLD', '2026-01-01', '2026-02-01', '100.00'));
        $authorization = new AuthorizationService($context);
        $updated = (new UpdateReceivable($receivables, $context, $context, $authorization, new ImmediateTransactionManager(), new AuditLogger($audits), $this->outputFactory()))
            ->execute($created->id, new UpdateReceivableInput('NEW', '2026-01-01', '2026-03-01', '150.00'));
        $cancelled = (new CancelReceivable($receivables, $context, $context, $authorization, new ImmediateTransactionManager(), new AuditLogger($audits), $this->outputFactory()))
            ->execute($created->id, new CancelReceivableInput('Pedido duplicado'));

        self::assertSame('150.00', $updated->originalAmount);
        self::assertSame('100.00', $audits->all()[1]->beforeData()['original_amount'] ?? null);
        self::assertSame('150.00', $audits->all()[1]->afterData()['original_amount'] ?? null);
        self::assertSame('CANCELLED', $cancelled->status);
        self::assertSame('RECEIVABLE_CANCELLED', $audits->all()[2]->action());
        self::assertSame('Pedido duplicado', $audits->all()[2]->metadata()['reason'] ?? null);
        self::assertSame($context->currentUser()->requireId(), $audits->all()[2]->afterData()['cancelled_by_user_id'] ?? null);
    }

    public function testCrossTenantReadUpdatePaymentCancelAndFiltersAreHidden(): void
    {
        [, $contextA, $customerA, $customers] = $this->context('A', 1);
        [, $contextB, $customerB] = $this->context('B', 2, MembershipRole::Owner, $customers);
        $receivables = new InMemoryReceivableRepository();
        $payments = new InMemoryReceivablePaymentRepository();
        $audits = new InMemoryAuditLogRepository();
        $createdB = $this->creator($receivables, $customers, $contextB, $audits)->execute(new CreateReceivableInput($customerB->requireId(), 'B-1', '2026-01-01', '2026-02-01', '100.00'));
        $authorizationA = new AuthorizationService($contextA);

        foreach ([
            fn () => (new GetReceivable($receivables, $payments, $contextA, $authorizationA, $this->outputFactory()))->execute($createdB->id, '2026-01-15'),
            fn () => (new UpdateReceivable($receivables, $contextA, $contextA, $authorizationA, new ImmediateTransactionManager(), new AuditLogger($audits), $this->outputFactory()))->execute($createdB->id, new UpdateReceivableInput('X', '2026-01-01', '2026-02-01', '100.00')),
            fn () => (new RegisterReceivablePayment($receivables, $payments, $contextA, $contextA, $authorizationA, new ImmediateTransactionManager(), new AuditLogger($audits), $this->outputFactory()))->execute($createdB->id, new RegisterReceivablePaymentInput('1.00', '2026-01-01')),
            fn () => (new CancelReceivable($receivables, $contextA, $contextA, $authorizationA, new ImmediateTransactionManager(), new AuditLogger($audits), $this->outputFactory()))->execute($createdB->id, new CancelReceivableInput('X')),
            fn () => (new ListReceivables($receivables, $customers, $contextA, $authorizationA, $this->outputFactory()))->execute(new ListReceivablesInput(customerId: $customerB->requireId(), referenceDate: '2026-01-15')),
            fn () => $this->creator($receivables, $customers, $contextA, $audits)->execute(new CreateReceivableInput($customerB->requireId(), 'X', '2026-01-01', '2026-02-01', '10.00')),
        ] as $operation) {
            try {
                $operation();
                self::fail('Cross-tenant operation must be hidden.');
            } catch (DomainException $exception) {
                self::assertSame(404, $exception->statusCode());
            }
        }

        $listA = (new ListReceivables($receivables, $customers, $contextA, $authorizationA, $this->outputFactory()))->execute(new ListReceivablesInput(referenceDate: '2026-01-15'));
        self::assertSame(0, $listA->total);
        self::assertSame($customerA->organization(), $contextA->currentOrganization());
    }

    public function testViewerCanReadButCannotWritePayOrCancel(): void
    {
        [$organization, $owner, $customer, $customers] = $this->context('A', 1);
        $viewer = $this->contextForOrganization($organization, 'viewer', 2, MembershipRole::Viewer);
        $receivables = new InMemoryReceivableRepository();
        $payments = new InMemoryReceivablePaymentRepository();
        $audits = new InMemoryAuditLogRepository();
        $created = $this->creator($receivables, $customers, $owner, $audits)->execute(new CreateReceivableInput($customer->requireId(), 'NF', '2026-01-01', '2026-02-01', '10.00'));
        $authorization = new AuthorizationService($viewer);

        self::assertSame($created->id, (new GetReceivable($receivables, $payments, $viewer, $authorization, $this->outputFactory()))->execute($created->id, '2026-01-15')->id);
        foreach ([
            fn () => $this->creator($receivables, $customers, $viewer, $audits)->execute(new CreateReceivableInput($customer->requireId(), 'X', '2026-01-01', '2026-02-01', '1.00')),
            fn () => (new UpdateReceivable($receivables, $viewer, $viewer, $authorization, new ImmediateTransactionManager(), new AuditLogger($audits), $this->outputFactory()))->execute($created->id, new UpdateReceivableInput('X', '2026-01-01', '2026-02-01', '10.00')),
            fn () => (new RegisterReceivablePayment($receivables, $payments, $viewer, $viewer, $authorization, new ImmediateTransactionManager(), new AuditLogger($audits), $this->outputFactory()))->execute($created->id, new RegisterReceivablePaymentInput('1.00', '2026-01-01')),
            fn () => (new CancelReceivable($receivables, $viewer, $viewer, $authorization, new ImmediateTransactionManager(), new AuditLogger($audits), $this->outputFactory()))->execute($created->id, new CancelReceivableInput('X')),
        ] as $operation) {
            $this->assertDomainError($operation, 'ACCESS_DENIED', 403);
        }
    }

    public function testOverdueFalseIncludesPaidCancelledAndUpcomingButExcludesOpenOverdue(): void
    {
        [$organization, $context, $customer, $customers] = $this->context('A', 1);
        $receivables = new InMemoryReceivableRepository();
        $now = new \DateTimeImmutable('2026-07-15');
        $overdue = Receivable::create($organization, $customer, 'MANUAL', null, 'OVERDUE', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-06-01'), new ReceivableAmount('10.00'), $now);
        $paid = Receivable::create($organization, $customer, 'MANUAL', null, 'PAID', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-06-01'), new ReceivableAmount('10.00'), $now);
        $paid->registerPayment(new ReceivableAmount('10.00'), new \DateTimeImmutable('2026-06-01'), $context->currentUser(), $now);
        $cancelled = Receivable::create($organization, $customer, 'MANUAL', null, 'CANCELLED', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-06-01'), new ReceivableAmount('10.00'), $now);
        $cancelled->cancel($context->currentUser(), 'Duplicado', $now);
        $upcoming = Receivable::create($organization, $customer, 'MANUAL', null, 'UPCOMING', new \DateTimeImmutable('2026-01-01'), new \DateTimeImmutable('2026-08-01'), new ReceivableAmount('10.00'), $now);
        foreach ([$overdue, $paid, $cancelled, $upcoming] as $receivable) {
            $receivables->save($organization, $receivable);
        }

        $result = (new ListReceivables($receivables, $customers, $context, new AuthorizationService($context), $this->outputFactory()))
            ->execute(new ListReceivablesInput(overdue: false, referenceDate: '2026-07-15'));
        $statuses = array_map(static fn ($receivable): string => $receivable->status, $result->receivables);

        self::assertSame(3, $result->total);
        self::assertContains('PAID', $statuses);
        self::assertContains('CANCELLED', $statuses);
        self::assertContains('OPEN', $statuses);
        self::assertNotContains('OVERDUE', $statuses);
    }

    public function testInvalidCreateAndUpdatePeriodsHaveStableError(): void
    {
        [, $context, $customer, $customers] = $this->context('A', 1);
        $receivables = new InMemoryReceivableRepository();
        $audits = new InMemoryAuditLogRepository();
        $this->assertDomainError(fn () => $this->creator($receivables, $customers, $context, $audits)->execute(new CreateReceivableInput($customer->requireId(), 'X', '2026-02-01', '2026-01-01', '1.00')), 'RECEIVABLE_INVALID_DATES', 422);
        $created = $this->creator($receivables, $customers, $context, $audits)->execute(new CreateReceivableInput($customer->requireId(), 'X', '2026-01-01', '2026-02-01', '1.00'));
        $this->assertDomainError(fn () => (new UpdateReceivable($receivables, $context, $context, new AuthorizationService($context), new ImmediateTransactionManager(), new AuditLogger($audits), $this->outputFactory()))->execute($created->id, new UpdateReceivableInput('X', '2026-03-01', '2026-02-01', '1.00')), 'RECEIVABLE_INVALID_DATES', 422);
    }

    private function creator(InMemoryReceivableRepository $receivables, InMemoryCustomerRepository $customers, CurrentContextStub $context, InMemoryAuditLogRepository $audits): CreateReceivable
    {
        return new CreateReceivable($receivables, $customers, $context, $context, new AuthorizationService($context), new ImmediateTransactionManager(), new AuditLogger($audits), $this->outputFactory());
    }

    private function outputFactory(): ReceivableOutputFactory
    {
        $resolver = new ReceivableStatusResolver();

        return new ReceivableOutputFactory($resolver, new AgingClassifier($resolver));
    }

    /** @return array{Organization, CurrentContextStub, Customer, InMemoryCustomerRepository} */
    private function context(string $suffix, int $id, MembershipRole $role = MembershipRole::Owner, ?InMemoryCustomerRepository $customers = null): array
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization '.$suffix, null, null, $now);
        EntityId::assign($organization, $id);
        $context = $this->contextForOrganization($organization, $suffix, $id, $role);
        $customer = Customer::create($organization, 'Customer '.$suffix, null, null, null, null, null, $now);
        $customers ??= new InMemoryCustomerRepository();
        $customers->save($organization, $customer);

        return [$organization, $context, $customer, $customers];
    }

    private function contextForOrganization(Organization $organization, string $suffix, int $id, MembershipRole $role): CurrentContextStub
    {
        $now = new \DateTimeImmutable();
        $user = User::create('User '.$suffix, $suffix.'@example.com', $now);
        EntityId::assign($user, $id);
        $membership = OrganizationMembership::join($organization, $user, $role, $now);
        EntityId::assign($membership, $id);

        return new CurrentContextStub($user, $organization, $membership);
    }

    /** @param callable(): mixed $operation */
    private function assertDomainError(callable $operation, string $code, int $status): void
    {
        try {
            $operation();
            self::fail('A domain error was expected.');
        } catch (DomainException $exception) {
            self::assertSame($code, $exception->errorCode());
            self::assertSame($status, $exception->statusCode());
        }
    }
}
