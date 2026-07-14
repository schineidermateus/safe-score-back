<?php

declare(strict_types=1);

namespace App\Tests\Customers\Application\UseCase;

use App\Customers\Application\DTO\CreateCustomerInput;
use App\Customers\Application\DTO\ListCustomersInput;
use App\Customers\Application\DTO\UpdateCustomerInput;
use App\Customers\Application\UseCase\CreateCustomer;
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
use PHPUnit\Framework\TestCase;

final class CustomerUseCasesTest extends TestCase
{
    public function testDatabaseRepositoryAssignsIntegerIdAndTenantIsEnforced(): void
    {
        $repository = new InMemoryCustomerRepository();
        [$organizationA, $contextA] = $this->context('A', 1);
        [, $contextB] = $this->context('B', 2);
        $createA = new CreateCustomer($repository, $contextA);

        $customer = $createA->execute(new CreateCustomerInput('Cliente A', document: '04.252.011/0001-10'));

        self::assertSame(1, $customer->id);
        self::assertIsInt($customer->id);
        self::assertSame($organizationA, $repository->findById($organizationA, $customer->id)?->organization());
        self::assertSame($customer->id, (new GetCustomer($repository, $contextA))->execute($customer->id)->id);

        $this->expectException(DomainException::class);
        (new GetCustomer($repository, $contextB))->execute($customer->id);
    }

    public function testListAndUpdateAreIsolatedByCurrentOrganization(): void
    {
        $repository = new InMemoryCustomerRepository();
        [, $contextA] = $this->context('A', 1);
        [, $contextB] = $this->context('B', 2);
        $customerA = (new CreateCustomer($repository, $contextA))->execute(new CreateCustomerInput('Cliente A'));
        $customerB = (new CreateCustomer($repository, $contextB))->execute(new CreateCustomerInput('Cliente B'));

        $list = (new ListCustomers($repository, $contextA))->execute(new ListCustomersInput());
        self::assertSame(1, $list->total);
        self::assertSame($customerA->id, $list->customers[0]->id);

        $this->expectException(DomainException::class);
        (new UpdateCustomer($repository, $contextA))->execute(
            $customerB->id,
            new UpdateCustomerInput('Tentativa cross-tenant'),
        );
    }

    public function testDocumentsAreUniquePerOrganizationButCanRepeatAcrossOrganizations(): void
    {
        $repository = new InMemoryCustomerRepository();
        [, $contextA] = $this->context('A', 1);
        [, $contextB] = $this->context('B', 2);
        $input = new CreateCustomerInput('Cliente', document: '04.252.011/0001-10');

        (new CreateCustomer($repository, $contextA))->execute($input);
        (new CreateCustomer($repository, $contextB))->execute($input);

        $this->expectException(DomainException::class);
        (new CreateCustomer($repository, $contextA))->execute($input);
    }

    /** @return array{Organization, CurrentContextStub} */
    private function context(string $suffix, int $id): array
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization '.$suffix, null, null, $now);
        $user = User::create('User '.$suffix, mb_strtolower($suffix).'@example.com', $now);
        EntityId::assign($organization, $id);
        EntityId::assign($user, $id);
        $membership = OrganizationMembership::join($organization, $user, MembershipRole::Owner, $now);
        EntityId::assign($membership, $id);

        return [$organization, new CurrentContextStub($user, $organization, $membership)];
    }
}
