<?php

declare(strict_types=1);

namespace App\Imports\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Imports\Application\DTO\ImportBatchOutput;
use App\Imports\Application\Processor\ImportProcessorRegistry;
use App\Imports\Domain\Repository\ImportBatchRepository;
use App\Imports\Domain\Repository\ImportRowRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class ProcessImportBatch
{
    public function __construct(
        private ImportBatchRepository $batches,
        private ImportRowRepository $rows,
        private ImportProcessorRegistry $processors,
        private CurrentOrganizationProviderInterface $currentOrganization,
        private CurrentUserProviderInterface $currentUser,
        private AuthorizationService $authorization,
        private TransactionManagerInterface $transactions,
        private AuditLogger $audit,
    ) {
    }

    public function execute(int $id): ImportBatchOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::ImportProcess);
        $organization = $this->currentOrganization->currentOrganization();
        $user = $this->currentUser->currentUser();
        $batch = $this->transactions->transactional(function () use ($organization, $id) {
            $batch = $this->batches->findByIdForUpdate($organization, $id)
                ?? throw new DomainException('IMPORT_NOT_FOUND', 'Lote de importação não encontrado.', 404);
            $batch->startProcessing(new \DateTimeImmutable());
            $this->batches->save($organization, $batch);

            return $batch;
        });
        $success = $skipped = $failed = 0;

        try {
            foreach ($this->rows->findValidForProcessing($organization, $batch) as $row) {
                try {
                    $result = $this->transactions->transactional(function () use ($row, $batch, $organization, $user) {
                        $data = $row->normalizedData()
                            ?? throw new DomainException('IMPORT_ROW_VALIDATION_FAILED', 'Linha sem dados normalizados.', 422);
                        $action = $row->action()
                            ?? throw new DomainException('IMPORT_ROW_VALIDATION_FAILED', 'Linha sem ação prevista.', 422);
                        $result = $this->processors->get($batch->type())->process($data, $action, $organization, $user);
                        if ($result->skipped) {
                            $row->markSkipped($result->entityType, $result->entityId, new \DateTimeImmutable());
                        } else {
                            $row->markProcessed($result->entityType, $result->entityId, new \DateTimeImmutable());
                        }
                        $this->rows->save($organization, $row);

                        return $result;
                    });
                    if ($result->skipped) {
                        ++$skipped;
                    } else {
                        ++$success;
                    }
                } catch (\Throwable $exception) {
                    $code = $exception instanceof DomainException ? $exception->errorCode() : 'IMPORT_PROCESSING_FAILED';
                    $message = $exception instanceof DomainException ? $exception->getMessage() : 'Falha técnica segura durante o processamento da linha.';
                    $this->transactions->transactional(function () use ($row, $organization, $code, $message): void {
                        $row->markFailed(['code' => $code, 'message' => $message], new \DateTimeImmutable());
                        $this->rows->save($organization, $row);
                    });
                    ++$failed;
                }
            }

            $finished = new \DateTimeImmutable();
            $this->transactions->transactional(function () use ($organization, $user, $id, $success, $skipped, $failed, $finished, &$batch): void {
                $batch = $this->batches->findByIdForUpdate($organization, $id)
                    ?? throw new DomainException('IMPORT_NOT_FOUND', 'Lote de importação não encontrado.', 404);
                $batch->finishProcessing($success, $skipped, $failed, $finished);
                $this->audit->record($organization, $user, 'IMPORT_PROCESSED', 'ImportBatch', $batch->requireId(), null, ['status' => $batch->status()->value], ['success_rows' => $success, 'skipped_rows' => $skipped, 'processing_error_rows' => $failed], $finished);
                $this->batches->save($organization, $batch);
            });
        } catch (\Throwable $exception) {
            try {
                $this->transactions->transactional(function () use ($organization, $user, $id, $success, $skipped, $failed): void {
                    $failedBatch = $this->batches->findByIdForUpdate($organization, $id);
                    if (null === $failedBatch || $failedBatch->status()->terminal()) {
                        return;
                    }
                    $now = new \DateTimeImmutable();
                    $failedBatch->failProcessing($success, $skipped, $failed, 'IMPORT_PROCESSING_FAILED', $now);
                    $this->audit->record($organization, $user, 'IMPORT_FAILED', 'ImportBatch', $failedBatch->requireId(), null, ['status' => $failedBatch->status()->value], ['failure_code' => 'IMPORT_PROCESSING_FAILED', 'success_rows' => $success, 'skipped_rows' => $skipped, 'processing_error_rows' => $failed], $now);
                    $this->batches->save($organization, $failedBatch);
                });
            } catch (\Throwable) {
                // Preserve the original failure if the database itself is unavailable.
            }

            throw $exception;
        }

        return ImportBatchOutput::fromEntity($batch);
    }
}
