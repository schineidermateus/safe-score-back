<?php

declare(strict_types=1);

namespace App\Receivables\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Customers\Domain\Repository\CustomerRepository;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Receivables\Application\DTO\CreateReceivableInput;
use App\Receivables\Application\DTO\ReceivableOutput;
use App\Receivables\Application\Service\ReceivableOutputFactory;
use App\Receivables\Domain\Entity\Receivable;
use App\Receivables\Domain\Repository\ReceivableRepository;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class CreateReceivable
{
    public function __construct(private ReceivableRepository $receivables, private CustomerRepository $customers,
        private CurrentOrganizationProviderInterface $currentOrganization, private CurrentUserProviderInterface $currentUser,
        private AuthorizationService $authorization, private TransactionManagerInterface $transactions, private AuditLogger $audit,
        private ReceivableOutputFactory $output)
    {
    }

    public function execute(CreateReceivableInput $input): ReceivableOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::ReceivableWrite);
        $organization = $this->currentOrganization->currentOrganization();
        $user = $this->currentUser->currentUser();
        $issue = ReceivableInput::date($input->issueDate, 'issue_date');
        $due = ReceivableInput::date($input->dueDate, 'due_date');
        ReceivableInput::assertValidPeriod($issue, $due);
        $amount = ReceivableInput::amount($input->originalAmount, 'original_amount');

        return $this->transactions->transactional(function () use ($input, $organization, $user, $issue, $due, $amount): ReceivableOutput {
            $customer = $this->customers->findById($organization, $input->customerId) ?? throw new DomainException('CUSTOMER_NOT_FOUND', 'Cliente não encontrado.', 404);
            if (null !== $input->externalId && $this->receivables->existsByExternalKey($organization, 'MANUAL', trim($input->externalId))) {
                throw new DomainException('RECEIVABLE_DUPLICATE_EXTERNAL_KEY', 'A chave externa já existe nesta origem.', 409, 'external_id');
            }
            try {
                $receivable = Receivable::create($organization, $customer, 'MANUAL', $input->externalId, $input->documentNumber, $issue, $due, $amount, new \DateTimeImmutable());
            } catch (\DomainException|\InvalidArgumentException) {
                throw new DomainException('RECEIVABLE_INVALID_DATES', 'Os dados do recebível são inválidos.', 422);
            }
            $this->receivables->save($organization, $receivable);
            $now = new \DateTimeImmutable();
            $this->audit->record($organization, $user, 'RECEIVABLE_CREATED', 'Receivable', $receivable->requireId(), null, ReceivableSnapshot::fromEntity($receivable), null, $now);

            return $this->output->create($receivable, new \DateTimeImmutable('today'));
        });
    }
}
