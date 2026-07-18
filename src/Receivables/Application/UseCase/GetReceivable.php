<?php

declare(strict_types=1);

namespace App\Receivables\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Receivables\Application\DTO\ReceivableOutput;
use App\Receivables\Application\Service\ReceivableOutputFactory;
use App\Receivables\Domain\Repository\ReceivablePaymentRepository;
use App\Receivables\Domain\Repository\ReceivableRepository;
use App\Shared\Domain\Exception\DomainException;

final readonly class GetReceivable
{
    public function __construct(private ReceivableRepository $receivables, private ReceivablePaymentRepository $payments,
        private CurrentOrganizationProviderInterface $currentOrganization, private AuthorizationService $authorization,
        private ReceivableOutputFactory $output)
    {
    }

    public function execute(int $id, ?string $referenceDate = null): ReceivableOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::ReceivableRead);
        $organization = $this->currentOrganization->currentOrganization();
        $receivable = $this->receivables->findByIdAndOrganization($id, $organization) ?? throw new DomainException('RECEIVABLE_NOT_FOUND', 'Recebível não encontrado.', 404);

        return $this->output->create($receivable, ReceivableInput::referenceDate($referenceDate), $this->payments->listByReceivableAndOrganization($receivable, $organization));
    }
}
