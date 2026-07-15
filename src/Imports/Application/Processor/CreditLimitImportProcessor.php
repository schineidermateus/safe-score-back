<?php

declare(strict_types=1);

namespace App\Imports\Application\Processor;

use App\Audit\Application\AuditLogger;
use App\Credit\Application\UseCase\CreditLimitSnapshot;
use App\Credit\Domain\Entity\CreditLimit;
use App\Credit\Domain\Repository\CreditLimitRepository;
use App\Credit\Domain\ValueObject\MoneyAmount;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Identity\Domain\Entity\User;
use App\Imports\Domain\Enum\ImportAction;
use App\Imports\Domain\Enum\ImportType;
use App\Organizations\Domain\Entity\Organization;
use App\Shared\Domain\Exception\DomainException;

final readonly class CreditLimitImportProcessor implements ImportProcessorInterface
{
    public function __construct(private CreditLimitRepository $limits, private CustomerRepository $customers, private AuditLogger $audit)
    {
    }

    public function supports(): ImportType
    {
        return ImportType::CreditLimits;
    }

    public function process(array $data, ImportAction $action, Organization $organization, User $user): ImportProcessResult
    {
        $customer = $this->customers->findById($organization, $data['customer_id']) ?? throw new DomainException('IMPORT_CUSTOMER_NOT_FOUND', 'Cliente não encontrado na organização atual.', 422);
        $from = new \DateTimeImmutable($data['valid_from']);
        $until = null === $data['valid_until'] ? null : new \DateTimeImmutable($data['valid_until']);
        $this->limits->lockCustomer($customer, $organization);
        $identical = $this->limits->findIdenticalActive($customer, $organization, $data['amount'], $from, $until, $data['reason']);
        if (null !== $identical) {
            return new ImportProcessResult('CreditLimit', $identical->requireId(), true);
        }
        if (ImportAction::Skip === $action) {
            throw new DomainException('IMPORT_PROCESSING_CONFLICT', 'Limite idêntico não foi encontrado durante o processamento.', 409);
        }
        if ($this->limits->existsOverlappingActivePeriod($customer, $organization, $from, $until)) {
            throw new DomainException('IMPORT_CREDIT_LIMIT_OVERLAP', 'Existe um limite ativo conflitante no período.', 409);
        }
        $now = new \DateTimeImmutable();
        $limit = CreditLimit::createActive($organization, $customer, new MoneyAmount($data['amount']), $from, $until, $data['reason'], $user, $now);
        $this->limits->save($organization, $limit);
        $this->audit->record($organization, $user, 'CREDIT_LIMIT_IMPORTED_CREATED', 'CreditLimit', $limit->requireId(), null, CreditLimitSnapshot::fromEntity($limit), ['source' => 'CSV_IMPORT'], $now);

        return new ImportProcessResult('CreditLimit', $limit->requireId(), false);
    }
}
