<?php

declare(strict_types=1);

namespace App\Imports\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Imports\Application\DTO\ImportBatchOutput;
use App\Imports\Application\Schema\ImportSchemaRegistry;
use App\Imports\Domain\Repository\ImportBatchRepository;
use App\Imports\Domain\Repository\ImportRowRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class SetImportMapping
{
    public function __construct(
        private ImportBatchRepository $batches,
        private ImportRowRepository $rows,
        private ImportSchemaRegistry $schemas,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private CurrentUserProviderInterface $currentUser,
        private AuthorizationService $authorization,
        private AuditLogger $audit,
        private TransactionManagerInterface $transactions,
    ) {
    }

    /** @param array<string, string> $mapping */
    public function execute(int $id, array $mapping): ImportBatchOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::ImportWrite);
        $organization = $this->currentOrganization->currentOrganization();
        $user = $this->currentUser->currentUser();
        $batch = $this->transactions->transactional(function () use ($organization, $user, $id, $mapping) {
            $batch = $this->batches->findByIdForUpdate($organization, $id)
                ?? throw new DomainException('IMPORT_NOT_FOUND', 'Lote de importação não encontrado.', 404);
            $this->schemas->validateMapping($batch->type(), $batch->headers(), $mapping);
            $now = new \DateTimeImmutable();
            $batch->setMapping($mapping, $now);
            $this->rows->deleteByBatch($organization, $batch);
            $this->audit->record($organization, $user, 'IMPORT_MAPPING_UPDATED', 'ImportBatch', $batch->requireId(), null, ['status' => $batch->status()->value], ['mapped_fields' => array_values($mapping)], $now);
            $this->batches->save($organization, $batch);

            return $batch;
        });

        return ImportBatchOutput::fromEntity($batch);
    }
}
