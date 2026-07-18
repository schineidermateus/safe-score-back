<?php

declare(strict_types=1);

namespace App\Receivables\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Receivables\Application\DTO\ListReceivablesInput;
use App\Receivables\Application\DTO\ReceivableListOutput;
use App\Receivables\Application\Service\ReceivableOutputFactory;
use App\Receivables\Domain\Enum\AgingBucket;
use App\Receivables\Domain\Enum\ReceivableStatus;
use App\Receivables\Domain\Repository\ReceivableCriteria;
use App\Receivables\Domain\Repository\ReceivableRepository;
use App\Shared\Domain\Exception\DomainException;

final readonly class ListReceivables
{
    public function __construct(private ReceivableRepository $receivables, private CustomerRepository $customers,
        private CurrentOrganizationProviderInterface $currentOrganization, private AuthorizationService $authorization,
        private ReceivableOutputFactory $output)
    {
    }

    public function execute(ListReceivablesInput $input): ReceivableListOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::ReceivableRead);
        $organization = $this->currentOrganization->currentOrganization();
        if (null !== $input->customerId && null === $this->customers->findById($organization, $input->customerId)) {
            throw new DomainException('CUSTOMER_NOT_FOUND', 'Cliente não encontrado.', 404);
        }
        $reference = ReceivableInput::referenceDate($input->referenceDate);
        $criteria = new ReceivableCriteria($input->customerId, null === $input->status ? null : ReceivableStatus::from($input->status), $input->overdue,
            null === $input->dueDateFrom ? null : ReceivableInput::date($input->dueDateFrom, 'due_date_from'), null === $input->dueDateTo ? null : ReceivableInput::date($input->dueDateTo, 'due_date_to'),
            null === $input->agingBucket ? null : AgingBucket::from($input->agingBucket), null === $input->amountMin ? null : (string) ReceivableInput::amount($input->amountMin, 'amount_min'),
            null === $input->amountMax ? null : (string) ReceivableInput::amount($input->amountMax, 'amount_max'), null === $input->search ? null : trim($input->search), $reference, $input->page, $input->perPage, $input->sort);
        if (null !== $criteria->dueDateFrom && null !== $criteria->dueDateTo && $criteria->dueDateTo < $criteria->dueDateFrom) {
            throw new DomainException('RECEIVABLE_INVALID_DATES', 'O período de vencimento é inválido.', 422);
        }
        if (null !== $criteria->amountMin && null !== $criteria->amountMax && (new \App\Receivables\Domain\ValueObject\ReceivableAmount($criteria->amountMin))->compare(new \App\Receivables\Domain\ValueObject\ReceivableAmount($criteria->amountMax)) > 0) {
            throw new DomainException('RECEIVABLE_INVALID_AMOUNT', 'A faixa de valores é inválida.', 422);
        }

        return new ReceivableListOutput(array_map(fn ($receivable) => $this->output->create($receivable, $reference), $this->receivables->list($organization, $criteria)), $input->page, $input->perPage, $this->receivables->countMatching($organization, $criteria));
    }
}
