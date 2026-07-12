<?php

declare(strict_types=1);

namespace App\Tests\Customers\Application\UseCase;

use App\Customers\Application\DTO\CreateCustomerInput;
use App\Customers\Application\DTO\ListCustomersInput;
use App\Customers\Application\DTO\UpdateCustomerInput;
use App\Customers\Application\UseCase\CreateCustomer;
use App\Customers\Application\UseCase\DeleteCustomer;
use App\Customers\Application\UseCase\GetCustomer;
use App\Customers\Application\UseCase\ListCustomers;
use App\Customers\Application\UseCase\UpdateCustomer;
use App\Organizations\Application\Context\OrganizationContext;
use App\Organizations\Domain\ValueObject\OrganizationId;
use App\Shared\Domain\Exception\DomainException;
use App\Tests\Customers\Support\InMemoryCustomerRepository;
use PHPUnit\Framework\TestCase;

final class CustomerUseCasesTest extends TestCase
{
    private InMemoryCustomerRepository $repository;
    private OrganizationContext $context;
    private CreateCustomer $createCustomer;

    protected function setUp(): void
    {
        $this->repository = new InMemoryCustomerRepository();
        $this->context = new OrganizationContext();
        $this->createCustomer = new CreateCustomer($this->repository, $this->context);
    }

    public function testItCreatesAndReadsACustomerInsideTheActiveOrganization(): void
    {
        $this->context->set(new OrganizationId('organization-a'));

        $created = $this->createCustomer->execute(new CreateCustomerInput(
            legalName: 'Empresa Cliente Ltda',
            tradeName: 'Empresa Cliente',
            document: '04.252.011/0001-10',
            externalId: 'CLI-001',
            segment: 'Distribuição',
        ));
        $found = (new GetCustomer($this->repository, $this->context))->execute($created->id);

        self::assertSame($created->id, $found->id);
        self::assertSame('04252011000110', $found->document);
        self::assertSame('ACTIVE', $found->status);
    }

    public function testTheSameDocumentCanExistInDifferentOrganizations(): void
    {
        $input = new CreateCustomerInput(
            legalName: 'Empresa Cliente Ltda',
            document: '04.252.011/0001-10',
        );

        $this->context->set(new OrganizationId('organization-a'));
        $first = $this->createCustomer->execute($input);

        $this->context->set(new OrganizationId('organization-b'));
        $second = $this->createCustomer->execute($input);

        self::assertNotSame($first->id, $second->id);
    }

    public function testItRejectsADuplicateDocumentInsideTheSameOrganization(): void
    {
        $this->context->set(new OrganizationId('organization-a'));
        $input = new CreateCustomerInput(
            legalName: 'Empresa Cliente Ltda',
            document: '04.252.011/0001-10',
        );
        $this->createCustomer->execute($input);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Já existe um cliente com este documento.');

        $this->createCustomer->execute($input);
    }

    public function testAClientFromAnotherOrganizationIsReportedAsNotFound(): void
    {
        $this->context->set(new OrganizationId('organization-a'));
        $customer = $this->createCustomer->execute(new CreateCustomerInput('Cliente A'));

        $this->context->set(new OrganizationId('organization-b'));

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Cliente não encontrado.');

        (new GetCustomer($this->repository, $this->context))->execute($customer->id);
    }

    public function testItListsOnlyCustomersFromTheActiveOrganization(): void
    {
        $this->context->set(new OrganizationId('organization-a'));
        $this->createCustomer->execute(new CreateCustomerInput('Cliente Alfa'));
        $this->createCustomer->execute(new CreateCustomerInput('Cliente Beta'));

        $this->context->set(new OrganizationId('organization-b'));
        $this->createCustomer->execute(new CreateCustomerInput('Cliente Externo'));

        $this->context->set(new OrganizationId('organization-a'));
        $result = (new ListCustomers($this->repository, $this->context))
            ->execute(new ListCustomersInput(page: 1, perPage: 10));

        self::assertSame(2, $result->total);
        self::assertSame(['Cliente Alfa', 'Cliente Beta'], array_column($result->customers, 'legalName'));
    }

    public function testItUpdatesAndSoftDeletesACustomer(): void
    {
        $this->context->set(new OrganizationId('organization-a'));
        $created = $this->createCustomer->execute(new CreateCustomerInput('Cliente Original'));
        $update = new UpdateCustomer($this->repository, $this->context);
        $updated = $update->execute($created->id, new UpdateCustomerInput(
            legalName: 'Cliente Atualizado',
            status: 'INACTIVE',
        ));

        self::assertSame('Cliente Atualizado', $updated->legalName);
        self::assertSame('INACTIVE', $updated->status);

        (new DeleteCustomer($this->repository, $this->context))->execute($created->id);

        $this->expectException(DomainException::class);
        (new GetCustomer($this->repository, $this->context))->execute($created->id);
    }

    public function testItRequiresAnOrganizationContext(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nenhuma organização ativa está disponível.');

        $this->createCustomer->execute(new CreateCustomerInput('Cliente sem tenant'));
    }

    public function testSoftDeletedCustomerDocumentRemainsReservedForAuditHistory(): void
    {
        $this->context->set(new OrganizationId('organization-a'));
        $input = new CreateCustomerInput(
            legalName: 'Cliente Original',
            document: '04.252.011/0001-10',
        );
        $created = $this->createCustomer->execute($input);
        (new DeleteCustomer($this->repository, $this->context))->execute($created->id);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Já existe um cliente com este documento.');

        $this->createCustomer->execute($input);
    }
}
