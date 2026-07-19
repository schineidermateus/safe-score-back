<?php

declare(strict_types=1);

namespace App\Imports\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Imports\Application\DTO\ImportBatchOutput;
use App\Imports\Domain\Repository\ImportBatchRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class CancelImportBatch
{
    public function __construct(
        private ImportBatchRepository $batches,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private CurrentUserProviderInterface $currentUser,
        private AuthorizationService $authorization,
        private AuditLogger $audit,
        private TransactionManagerInterface $transactions,
    ) {
    }

    public function execute(int $id): ImportBatchOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::ImportWrite);
        $organization = $this->currentOrganization->currentOrganization();
        $user = $this->currentUser->currentUser();
        $batch = $this->transactions->transactional(function () use ($organization, $user, $id) {
            $batch = $this->batches->findByIdForUpdate($organization, $id)
                ?? throw new DomainException('IMPORT_NOT_FOUND', 'Lote de importação não encontrado.', 404);
            $now = new \DateTimeImmutable();
            $batch->cancel($now);
            $this->audit->record($organization, $user, 'IMPORT_CANCELLED', 'ImportBatch', $batch->requireId(), null, ['status' => $batch->status()->value], null, $now);
            $this->batches->save($organization, $batch);

            return $batch;
        });

        return ImportBatchOutput::fromEntity($batch);
    }
}
