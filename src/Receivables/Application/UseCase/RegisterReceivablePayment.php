<?php

declare(strict_types=1);

namespace App\Receivables\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Receivables\Application\DTO\ReceivableOutput;
use App\Receivables\Application\DTO\RegisterReceivablePaymentInput;
use App\Receivables\Application\Service\ReceivableOutputFactory;
use App\Receivables\Domain\Repository\ReceivablePaymentRepository;
use App\Receivables\Domain\Repository\ReceivableRepository;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class RegisterReceivablePayment
{
    public function __construct(private ReceivableRepository $receivables, private ReceivablePaymentRepository $payments,
        private CurrentOrganizationProviderInterface $currentOrganization, private CurrentUserProviderInterface $currentUser,
        private AuthorizationService $authorization, private TransactionManagerInterface $transactions, private AuditLogger $audit,
        private ReceivableOutputFactory $output)
    {
    }

    public function execute(int $id, RegisterReceivablePaymentInput $input): ReceivableOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::ReceivablePaymentRegister);
        $organization = $this->currentOrganization->currentOrganization();
        $user = $this->currentUser->currentUser();
        $amount = ReceivableInput::amount($input->amount);
        if (!$amount->isPositive()) {
            throw new DomainException('RECEIVABLE_INVALID_AMOUNT', 'O pagamento deve ser maior que zero.', 422, 'amount');
        }
        $date = ReceivableInput::date($input->paymentDate, 'payment_date');

        return $this->transactions->transactional(function () use ($id, $organization, $user, $amount, $date): ReceivableOutput {
            $receivable = $this->receivables->findByIdAndOrganizationForUpdate($id, $organization) ?? throw new DomainException('RECEIVABLE_NOT_FOUND', 'Recebível não encontrado.', 404);
            $before = ReceivableSnapshot::fromEntity($receivable);
            $now = new \DateTimeImmutable();
            try {
                $payment = $receivable->registerPayment($amount, $date, $user, $now);
            } catch (\InvalidArgumentException) {
                throw new DomainException('RECEIVABLE_INVALID_DATES', 'A data do pagamento não pode ser anterior à emissão.', 422, 'payment_date');
            } catch (\DomainException $e) {
                $code = str_contains($e->getMessage(), 'exceeds') ? 'RECEIVABLE_PAYMENT_EXCEEDS_BALANCE' : 'RECEIVABLE_PAYMENT_NOT_ALLOWED';
                throw new DomainException($code, 'Pagamento não permitido para este recebível.', 409);
            }
            $this->receivables->save($organization, $receivable);
            $this->payments->save($organization, $payment);
            $this->audit->record($organization, $user, 'RECEIVABLE_PAYMENT_REGISTERED', 'Receivable', $id, $before, ReceivableSnapshot::fromEntity($receivable), ['payment_id' => $payment->requireId(), 'amount' => $payment->amount(), 'payment_date' => $payment->paymentDate()->format('Y-m-d')], $now);

            return $this->output->create($receivable, new \DateTimeImmutable('today'), $this->payments->listByReceivableAndOrganization($receivable, $organization));
        });
    }
}
