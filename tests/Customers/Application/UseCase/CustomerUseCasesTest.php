<?php

declare(strict_types=1);

namespace App\Tests\Customers\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Customers\Application\DTO\CreateCustomerInput;
use App\Customers\Application\DTO\ListCustomersInput;
use App\Customers\Application\DTO\UpdateCustomerInput;
use App\Customers\Application\UseCase\CreateCustomer;
use App\Customers\Application\UseCase\DeleteCustomer;
use App\Customers\Application\UseCase\GetCustomer;
use App\Customers\Application\UseCase\ListCustomers;
use App\Customers\Application\UseCase\UpdateCustomer;
use App\Identity\Domain\Entity\User;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Customers\Support\InMemoryCustomerRepository;
use App\Tests\Support\CurrentContextStub;
use App\Tests\Support\EntityId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CustomerUseCasesTest extends TestCase
{
    public function testInMemoryRepositoryAssignsIntegerIdAndTenantIsEnforced(): void
    {
        $repository = new InMemoryCustomerRepository();
        [$organizationA, $contextA] = $this->context('A', 1);
        [, $contextB] = $this->context('B', 2);
        $createA = new CreateCustomer($repository, $contextA, new AuthorizationService($contextA));

        $customer = $createA->execute(new CreateCustomerInput('Cliente A', document: '04.252.011/0001-10'));

        self::assertSame(1, $customer->id);
        self::assertIsInt($customer->id);
        self::assertSame($organizationA, $repository->findById($organizationA, $customer->id)?->organization());
        self::assertSame($customer->id, (new GetCustomer($repository, $contextA, new AuthorizationService($contextA)))->execute($customer->id)->id);

        try {
            (new GetCustomer($repository, $contextB, new AuthorizationService($contextB)))->execute($customer->id);
            self::fail('Cross-tenant read should have been rejected.');
        } catch (DomainException $exception) {
            self::assertSame(404, $exception->statusCode());
            self::assertSame('CUSTOMER_NOT_FOUND', $exception->errorCode());
            self::assertSame('Cliente não encontrado.', $exception->getMessage());
            self::assertStringNotContainsString('Cliente A', $exception->getMessage());
            self::assertStringNotContainsString('04252011000110', $exception->getMessage());
        }
    }

    public function testListAndUpdateAreIsolatedByCurrentOrganization(): void
    {
        $repository = new InMemoryCustomerRepository();
        [, $contextA] = $this->context('A', 1);
        [, $contextB] = $this->context('B', 2);
        $customerA = (new CreateCustomer($repository, $contextA, new AuthorizationService($contextA)))->execute(new CreateCustomerInput('Cliente A'));
        $customerB = (new CreateCustomer($repository, $contextB, new AuthorizationService($contextB)))->execute(new CreateCustomerInput('Cliente B'));

        $list = (new ListCustomers($repository, $contextA, new AuthorizationService($contextA)))->execute(new ListCustomersInput());
        self::assertSame(1, $list->total);
        self::assertSame($customerA->id, $list->customers[0]->id);

        $this->expectException(DomainException::class);
        (new UpdateCustomer($repository, $contextA, new AuthorizationService($contextA)))->execute(
            $customerB->id,
            new UpdateCustomerInput('Tentativa cross-tenant'),
        );
    }

    public function testDeleteIsIsolatedByCurrentOrganization(): void
    {
        $repository = new InMemoryCustomerRepository();
        [, $contextA] = $this->context('A', 1);
        [, $contextB] = $this->context('B', 2);
        $customerB = (new CreateCustomer($repository, $contextB, new AuthorizationService($contextB)))
            ->execute(new CreateCustomerInput('Cliente B'));

        $this->expectException(DomainException::class);
        (new DeleteCustomer($repository, $contextA, new AuthorizationService($contextA)))->execute($customerB->id);
    }

    public function testFiltersNeverRemoveOrganizationScope(): void
    {
        $repository = new InMemoryCustomerRepository();
        [, $contextA] = $this->context('A', 1);
        [, $contextB] = $this->context('B', 2);
        (new CreateCustomer($repository, $contextA, new AuthorizationService($contextA)))
            ->execute(new CreateCustomerInput('Alfa local'));
        (new CreateCustomer($repository, $contextB, new AuthorizationService($contextB)))
            ->execute(new CreateCustomerInput('Alfa externo'));

        $result = (new ListCustomers($repository, $contextA, new AuthorizationService($contextA)))
            ->execute(new ListCustomersInput(search: 'Alfa'));

        self::assertSame(1, $result->total);
        self::assertSame('Alfa local', $result->customers[0]->legalName);
    }

    public function testDocumentsAreUniquePerOrganizationButCanRepeatAcrossOrganizations(): void
    {
        $repository = new InMemoryCustomerRepository();
        [, $contextA] = $this->context('A', 1);
        [, $contextB] = $this->context('B', 2);
        $input = new CreateCustomerInput('Cliente', document: '04.252.011/0001-10');

        (new CreateCustomer($repository, $contextA, new AuthorizationService($contextA)))->execute($input);
        (new CreateCustomer($repository, $contextB, new AuthorizationService($contextB)))->execute($input);

        $this->expectException(DomainException::class);
        (new CreateCustomer($repository, $contextA, new AuthorizationService($contextA)))->execute($input);
    }

    public function testMultipleCustomersWithoutDocumentAreAllowedInTheSameOrganization(): void
    {
        $repository = new InMemoryCustomerRepository();
        [, $context] = $this->context('A', 1);
        $create = new CreateCustomer($repository, $context, new AuthorizationService($context));

        $first = $create->execute(new CreateCustomerInput('Sem documento A'));
        $second = $create->execute(new CreateCustomerInput('Sem documento B'));

        self::assertNotSame($first->id, $second->id);
        self::assertNull($first->document);
        self::assertNull($second->document);
    }

    public function testSoftDeletedCustomerStaysHiddenAndKeepsItsDocumentReserved(): void
    {
        $repository = new InMemoryCustomerRepository();
        [, $context] = $this->context('A', 1);
        $authorization = new AuthorizationService($context);
        $create = new CreateCustomer($repository, $context, $authorization);
        $input = new CreateCustomerInput('Cliente excluído', document: '04.252.011/0001-10');
        $customer = $create->execute($input);

        (new DeleteCustomer($repository, $context, $authorization))->execute($customer->id);

        $list = (new ListCustomers($repository, $context, $authorization))->execute(new ListCustomersInput());
        self::assertSame(0, $list->total);

        try {
            (new GetCustomer($repository, $context, $authorization))->execute($customer->id);
            self::fail('Soft-deleted customer should not be readable.');
        } catch (DomainException $exception) {
            self::assertSame('CUSTOMER_NOT_FOUND', $exception->errorCode());
            self::assertSame(404, $exception->statusCode());
        }

        try {
            $create->execute($input);
            self::fail('Soft delete should not release the document uniqueness.');
        } catch (DomainException $exception) {
            self::assertSame('CUSTOMER_DOCUMENT_ALREADY_EXISTS', $exception->errorCode());
            self::assertSame(409, $exception->statusCode());
        }
    }

    public function testViewerCannotCreateCustomerEvenWhenUseCaseIsCalledDirectly(): void
    {
        $repository = new InMemoryCustomerRepository();
        [, $viewerContext] = $this->context('viewer', 1, MembershipRole::Viewer);

        $this->expectException(DomainException::class);
        (new CreateCustomer($repository, $viewerContext, new AuthorizationService($viewerContext)))
            ->execute(new CreateCustomerInput('Cliente não autorizado'));
    }

    #[DataProvider('customerWriterRoles')]
    public function testAuthorizedRolesCanCreateAndUpdateCustomers(MembershipRole $role): void
    {
        $repository = new InMemoryCustomerRepository();
        [, $context] = $this->context($role->value, 1, $role);
        $authorization = new AuthorizationService($context);
        $created = (new CreateCustomer($repository, $context, $authorization))
            ->execute(new CreateCustomerInput('Cliente'));

        $updated = (new UpdateCustomer($repository, $context, $authorization))
            ->execute($created->id, new UpdateCustomerInput('Cliente atualizado'));

        self::assertSame('Cliente atualizado', $updated->legalName);
    }

    /** @return iterable<string, array{MembershipRole}> */
    public static function customerWriterRoles(): iterable
    {
        yield 'owner' => [MembershipRole::Owner];
        yield 'admin' => [MembershipRole::Admin];
        yield 'analyst' => [MembershipRole::Analyst];
    }

    #[DataProvider('viewerWriteOperations')]
    public function testViewerCannotMutateCustomers(string $operation): void
    {
        $repository = new InMemoryCustomerRepository();
        [, $ownerContext] = $this->context('owner', 1);
        [, $viewerContext] = $this->context('viewer', 1, MembershipRole::Viewer);
        $customer = (new CreateCustomer($repository, $ownerContext, new AuthorizationService($ownerContext)))
            ->execute(new CreateCustomerInput('Cliente'));

        $this->expectException(DomainException::class);

        if ('update' === $operation) {
            (new UpdateCustomer($repository, $viewerContext, new AuthorizationService($viewerContext)))
                ->execute($customer->id, new UpdateCustomerInput('Proibido'));

            return;
        }

        (new DeleteCustomer($repository, $viewerContext, new AuthorizationService($viewerContext)))
            ->execute($customer->id);
    }

    /** @return iterable<string, array{string}> */
    public static function viewerWriteOperations(): iterable
    {
        yield 'update' => ['update'];
        yield 'delete' => ['delete'];
    }

    /** @return array{Organization, CurrentContextStub} */
    private function context(string $suffix, int $id, MembershipRole $role = MembershipRole::Owner): array
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization '.$suffix, null, null, $now);
        $user = User::create('User '.$suffix, mb_strtolower($suffix).'@example.com', $now);
        EntityId::assign($organization, $id);
        EntityId::assign($user, $id);
        $membership = OrganizationMembership::join($organization, $user, $role, $now);
        EntityId::assign($membership, $id);

        return [$organization, new CurrentContextStub($user, $organization, $membership)];
    }
}
