<?php

declare(strict_types=1);

namespace App\Imports\Application\Processor;

use App\Audit\Application\AuditLogger;
use App\Customers\Domain\Entity\Customer;
use App\Customers\Domain\Enum\CustomerStatus;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Identity\Domain\Entity\User;
use App\Imports\Application\Service\CustomerImportResolver;
use App\Imports\Domain\Enum\ImportAction;
use App\Imports\Domain\Enum\ImportType;
use App\Organizations\Domain\Entity\Organization;
use App\Shared\Domain\Exception\DomainException;

final readonly class CustomerImportProcessor implements ImportProcessorInterface
{
    public function __construct(private CustomerRepository $customers, private CustomerImportResolver $resolver, private AuditLogger $audit)
    {
    }

    public function supports(): ImportType
    {
        return ImportType::Customers;
    }

    public function process(array $data, ImportAction $action, Organization $organization, User $user): ImportProcessResult
    {
        $existing = $this->resolver->resolve($organization, $data['external_id'], $data['document']);
        if (null !== $existing && $this->matches($existing, $data)) {
            return new ImportProcessResult('Customer', $existing->requireId(), true);
        }
        if (null !== $existing && ImportAction::Create === $action) {
            throw new DomainException('IMPORT_PROCESSING_CONFLICT', 'Cliente foi criado ou alterado após a validação.', 409);
        }
        if (ImportAction::Skip === $action) {
            throw new DomainException('IMPORT_PROCESSING_CONFLICT', 'Cliente idêntico não foi encontrado durante o processamento.', 409);
        }
        $now = new \DateTimeImmutable();
        $before = null;
        if (null === $existing) {
            if (ImportAction::Update === $action) {
                throw new DomainException('IMPORT_PROCESSING_CONFLICT', 'Cliente previsto para atualização não foi encontrado.', 409);
            }
            $customer = Customer::create($organization, $data['legal_name'], $data['trade_name'], $data['document'], $data['external_id'], null, null, $now);
            if ('INACTIVE' === $data['status']) {
                $customer->update($data['legal_name'], $data['trade_name'], $data['document'], $data['external_id'], null, null, CustomerStatus::Inactive, $now);
            }
            $event = 'CUSTOMER_IMPORTED_CREATED';
        } else {
            $customer = $existing;
            $before = $this->snapshot($customer);
            $customer->update($data['legal_name'], $data['trade_name'], $data['document'], $data['external_id'], $customer->segment(), $customer->accountManager(), CustomerStatus::from($data['status']), $now);
            $event = 'CUSTOMER_IMPORTED_UPDATED';
        }
        $this->customers->save($organization, $customer);
        $this->audit->record($organization, $user, $event, 'Customer', $customer->requireId(), $before, $this->snapshot($customer), ['source' => 'CSV_IMPORT'], $now);

        return new ImportProcessResult('Customer', $customer->requireId(), false);
    }

    /** @return array<string, mixed> */
    private function snapshot(Customer $customer): array
    {
        return ['id' => $customer->requireId(), 'external_id' => $customer->externalId(), 'legal_name' => $customer->legalName(), 'trade_name' => $customer->tradeName(), 'document' => $customer->document(), 'status' => $customer->status()->value];
    }

    /** @param array<string, mixed> $data */
    private function matches(Customer $customer, array $data): bool
    {
        return $customer->externalId() === $data['external_id'] && $customer->legalName() === $data['legal_name'] && $customer->tradeName() === $data['trade_name'] && $customer->document() === $data['document'] && $customer->status()->value === $data['status'];
    }
}
