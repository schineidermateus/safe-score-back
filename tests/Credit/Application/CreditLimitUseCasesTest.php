<?php

declare(strict_types=1);

namespace App\Tests\Credit\Application;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Credit\Application\DTO\CreateCreditLimitInput;
use App\Credit\Application\DTO\GetActiveCreditLimitInput;
use App\Credit\Application\DTO\ListCreditLimitsInput;
use App\Credit\Application\DTO\RevokeCreditLimitInput;
use App\Credit\Application\DTO\UpdateCreditLimitInput;
use App\Credit\Application\Service\ActiveCreditLimitResolver;
use App\Credit\Application\UseCase\CreateCreditLimit;
use App\Credit\Application\UseCase\GetActiveCreditLimit;
use App\Credit\Application\UseCase\GetCreditLimit;
use App\Credit\Application\UseCase\ListCustomerCreditLimitHistory;
use App\Credit\Application\UseCase\RevokeCreditLimit;
use App\Credit\Application\UseCase\UpdateCreditLimit;
use App\Customers\Domain\Entity\Customer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Audit\Support\InMemoryAuditLogRepository;
use App\Tests\Credit\Support\InMemoryCreditLimitRepository;
use App\Tests\Customers\Support\InMemoryCustomerRepository;
use App\Tests\Support\CurrentContextStub;
use App\Tests\Support\EntityId;
use App\Tests\Support\ImmediateTransactionManager;
use PHPUnit\Framework\TestCase;

final class CreditLimitUseCasesTest extends TestCase
{
    public function testCreateUsesCurrentTenantAndUserAndWritesAudit(): void
    {
        [$organization, $context, $customer, $customers] = $this->context('A', 1);
        $limits = new InMemoryCreditLimitRepository();
        $audits = new InMemoryAuditLogRepository();

        $output = $this->creator($limits, $customers, $context, $audits)->execute(
            $customer->requireId(),
            new CreateCreditLimitInput('500000.00', '2026-07-01', null, 'Revisão inicial aprovada'),
        );

        self::assertGreaterThan(0, $output->id);
        self::assertSame('500000.00', $output->amount);
        self::assertSame($context->currentUser()->requireId(), $output->approvedByUserId);
        self::assertSame($organization, $limits->all()[0]->organization());
        self::assertSame('CREDIT_LIMIT_CREATED', $audits->all()[0]->action());
        self::assertSame($context->currentUser(), $audits->all()[0]->user());
        self::assertSame('Revisão inicial aprovada', $audits->all()[0]->metadata()['reason'] ?? null);
    }

    public function testCreateRejectsOverlapInsideTransaction(): void
    {
        [, $context, $customer, $customers] = $this->context('A', 1);
        $limits = new InMemoryCreditLimitRepository();
        $audits = new InMemoryAuditLogRepository();
        $create = $this->creator($limits, $customers, $context, $audits);
        $create->execute($customer->requireId(), new CreateCreditLimitInput('10.00', '2026-07-01', '2026-07-31', 'first'));

        try {
            $create->execute($customer->requireId(), new CreateCreditLimitInput('20.00', '2026-07-31', null, 'second'));
            self::fail('Overlap should fail.');
        } catch (DomainException $exception) {
            self::assertSame('CREDIT_LIMIT_OVERLAP', $exception->errorCode());
            self::assertSame(409, $exception->statusCode());
        }
    }

    public function testInvalidReasonReturnsStableValidationError(): void
    {
        [, $context, $customer, $customers] = $this->context('A', 1);

        try {
            $this->creator(new InMemoryCreditLimitRepository(), $customers, $context, new InMemoryAuditLogRepository())
                ->execute($customer->requireId(), new CreateCreditLimitInput('10.00', '2026-01-01', null, '   '));
            self::fail('Blank reason should fail.');
        } catch (DomainException $exception) {
            self::assertSame('CREDIT_LIMIT_INVALID_REASON', $exception->errorCode());
            self::assertSame(422, $exception->statusCode());
            self::assertSame('reason', $exception->field());
        }
    }

    public function testGetActiveGetByIdAndHistoryReturnTenantScopedDecimalOutputs(): void
    {
        [, $context, $customer, $customers] = $this->context('A', 1);
        $limits = new InMemoryCreditLimitRepository();
        $audits = new InMemoryAuditLogRepository();
        $create = $this->creator($limits, $customers, $context, $audits);
        $old = $create->execute($customer->requireId(), new CreateCreditLimitInput('10.50', '2025-01-01', '2025-12-31', 'old'));
        $current = $create->execute($customer->requireId(), new CreateCreditLimitInput('20.75', '2026-01-01', null, 'current'));
        $authorization = new AuthorizationService($context);

        $found = (new GetCreditLimit($limits, $context, $authorization))->execute($current->id);
        $active = (new GetActiveCreditLimit(new ActiveCreditLimitResolver($limits), $customers, $context, $authorization))
            ->execute($customer->requireId(), new GetActiveCreditLimitInput('2026-07-15'));
        $history = (new ListCustomerCreditLimitHistory($limits, $customers, $context, $authorization))
            ->execute($customer->requireId(), new ListCreditLimitsInput());

        self::assertSame('20.75', $found->amount);
        self::assertSame($current->id, $active?->id);
        self::assertSame(2, $history->total);
        self::assertSame([$current->id, $old->id], array_map(static fn ($limit): int => $limit->id, $history->creditLimits));
        self::assertArrayNotHasKey('organization_id', $found->toArray());
    }

    public function testUpdateAndRevokePreserveBeforeAfterAndReason(): void
    {
        [, $context, $customer, $customers] = $this->context('A', 1);
        $limits = new InMemoryCreditLimitRepository();
        $audits = new InMemoryAuditLogRepository();
        $created = $this->creator($limits, $customers, $context, $audits)->execute(
            $customer->requireId(),
            new CreateCreditLimitInput('10.00', '2026-01-01', null, 'created'),
        );
        $authorization = new AuthorizationService($context);
        $updated = (new UpdateCreditLimit(
            $limits,
            $context,
            $context,
            $authorization,
            new ImmediateTransactionManager(),
            new AuditLogger($audits),
        ))->execute($created->id, new UpdateCreditLimitInput('20.00', '2026-02-01', null, 'updated'));
        self::assertSame('20.00', $updated->amount);
        self::assertSame('10.00', $audits->all()[1]->beforeData()['amount'] ?? null);
        self::assertSame('20.00', $audits->all()[1]->afterData()['amount'] ?? null);

        $revoked = (new RevokeCreditLimit(
            $limits,
            $context,
            $context,
            $authorization,
            new ImmediateTransactionManager(),
            new AuditLogger($audits),
        ))->execute($created->id, new RevokeCreditLimitInput('Risco elevado'));
        self::assertSame('REVOKED', $revoked->status);
        self::assertSame('CREDIT_LIMIT_REVOKED', $audits->all()[2]->action());
        self::assertSame('Risco elevado', $audits->all()[2]->metadata()['reason'] ?? null);
    }

    public function testCrossTenantReadUpdateRevokeAndHistoryAreHiddenAsNotFound(): void
    {
        [, $contextA, $customerA, $customers] = $this->context('A', 1);
        [, $contextB, $customerB] = $this->context('B', 2, MembershipRole::Owner, $customers);
        $limits = new InMemoryCreditLimitRepository();
        $audits = new InMemoryAuditLogRepository();
        $createdB = $this->creator($limits, $customers, $contextB, $audits)->execute(
            $customerB->requireId(),
            new CreateCreditLimitInput('10.00', '2026-01-01', null, 'tenant B'),
        );
        $authorizationA = new AuthorizationService($contextA);

        foreach ([
            static fn () => (new CreateCreditLimit($limits, $customers, $contextA, $contextA, $authorizationA, new ImmediateTransactionManager(), new AuditLogger($audits)))->execute($customerB->requireId(), new CreateCreditLimitInput('30.00', '2027-01-01', null, 'forbidden')),
            static fn () => (new GetCreditLimit($limits, $contextA, $authorizationA))->execute($createdB->id),
            static fn () => (new GetActiveCreditLimit(new ActiveCreditLimitResolver($limits), $customers, $contextA, $authorizationA))->execute($customerB->requireId(), new GetActiveCreditLimitInput('2026-07-01')),
            static fn () => (new UpdateCreditLimit($limits, $contextA, $contextA, $authorizationA, new ImmediateTransactionManager(), new AuditLogger($audits)))->execute($createdB->id, new UpdateCreditLimitInput('20.00', '2026-01-01', null, 'forbidden')),
            static fn () => (new RevokeCreditLimit($limits, $contextA, $contextA, $authorizationA, new ImmediateTransactionManager(), new AuditLogger($audits)))->execute($createdB->id, new RevokeCreditLimitInput('forbidden')),
            static fn () => (new ListCustomerCreditLimitHistory($limits, $customers, $contextA, $authorizationA))->execute($customerB->requireId(), new ListCreditLimitsInput()),
        ] as $operation) {
            try {
                $operation();
                self::fail('Cross-tenant operation should fail.');
            } catch (DomainException $exception) {
                self::assertSame(404, $exception->statusCode());
            }
        }

        self::assertSame(0, (new ListCustomerCreditLimitHistory($limits, $customers, $contextA, $authorizationA))->execute($customerA->requireId(), new ListCreditLimitsInput())->total);
    }

    public function testViewerCannotWriteAndAnalystCannotRevoke(): void
    {
        [, $ownerContext, $customer, $customers] = $this->context('A', 1);
        [, $viewerContext] = $this->sameOrganizationContext($ownerContext->currentOrganization(), 'viewer', 2, MembershipRole::Viewer);
        [, $analystContext] = $this->sameOrganizationContext($ownerContext->currentOrganization(), 'analyst', 3, MembershipRole::Analyst);
        $limits = new InMemoryCreditLimitRepository();
        $audits = new InMemoryAuditLogRepository();
        $created = $this->creator($limits, $customers, $ownerContext, $audits)->execute($customer->requireId(), new CreateCreditLimitInput('10.00', '2026-01-01', null, 'created'));

        try {
            $this->creator($limits, $customers, $viewerContext, $audits)->execute($customer->requireId(), new CreateCreditLimitInput('20.00', '2027-01-01', null, 'forbidden'));
            self::fail('Viewer write should fail.');
        } catch (DomainException $exception) {
            self::assertSame(403, $exception->statusCode());
        }

        $this->expectException(DomainException::class);
        (new RevokeCreditLimit($limits, $analystContext, $analystContext, new AuthorizationService($analystContext), new ImmediateTransactionManager(), new AuditLogger($audits)))
            ->execute($created->id, new RevokeCreditLimitInput('forbidden'));
    }

    private function creator(
        InMemoryCreditLimitRepository $limits,
        InMemoryCustomerRepository $customers,
        CurrentContextStub $context,
        InMemoryAuditLogRepository $audits,
    ): CreateCreditLimit {
        return new CreateCreditLimit(
            $limits,
            $customers,
            $context,
            $context,
            new AuthorizationService($context),
            new ImmediateTransactionManager(),
            new AuditLogger($audits),
        );
    }

    /** @return array{Organization, CurrentContextStub, Customer, InMemoryCustomerRepository} */
    private function context(
        string $suffix,
        int $id,
        MembershipRole $role = MembershipRole::Owner,
        ?InMemoryCustomerRepository $customers = null,
    ): array {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization '.$suffix, null, null, $now);
        EntityId::assign($organization, $id);
        [, $context] = $this->sameOrganizationContext($organization, $suffix, $id, $role);
        $customer = Customer::create($organization, 'Customer '.$suffix, null, null, null, null, null, $now);
        $customers ??= new InMemoryCustomerRepository();
        $customers->save($organization, $customer);

        return [$organization, $context, $customer, $customers];
    }

    /** @return array{User, CurrentContextStub} */
    private function sameOrganizationContext(Organization $organization, string $suffix, int $id, MembershipRole $role): array
    {
        $now = new \DateTimeImmutable();
        $user = User::create('User '.$suffix, $suffix.'@example.com', $now);
        EntityId::assign($user, $id);
        $membership = OrganizationMembership::join($organization, $user, $role, $now);
        EntityId::assign($membership, $id);

        return [$user, new CurrentContextStub($user, $organization, $membership)];
    }
}
