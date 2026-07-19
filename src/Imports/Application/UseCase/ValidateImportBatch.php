<?php

declare(strict_types=1);

namespace App\Imports\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Imports\Application\DTO\ImportBatchOutput;
use App\Imports\Application\Port\CsvReaderInterface;
use App\Imports\Application\Port\ImportFileStorageInterface;
use App\Imports\Application\Schema\ImportSchemaRegistry;
use App\Imports\Application\Validation\ImportRowValidatorRegistry;
use App\Imports\Domain\Entity\ImportRow;
use App\Imports\Domain\Repository\ImportBatchRepository;
use App\Imports\Domain\Repository\ImportRowRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class ValidateImportBatch
{
    public function __construct(
        private ImportBatchRepository $batches,
        private ImportRowRepository $rows,
        private ImportFileStorageInterface $storage,
        private CsvReaderInterface $csv,
        private ImportSchemaRegistry $schemas,
        private ImportRowValidatorRegistry $validators,
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
        $batch = $this->transactions->transactional(function () use ($organization, $id) {
            $batch = $this->batches->findByIdForUpdate($organization, $id)
                ?? throw new DomainException('IMPORT_NOT_FOUND', 'Lote de importação não encontrado.', 404);
            $mapping = $batch->mapping()
                ?? throw new DomainException('IMPORT_INVALID_MAPPING', 'Configure o mapeamento antes de validar.', 409);
            $this->schemas->validateMapping($batch->type(), $batch->headers(), $mapping);
            $batch->startValidation(new \DateTimeImmutable());
            $this->rows->deleteByBatch($organization, $batch);
            $this->batches->save($organization, $batch);

            return $batch;
        });
        $mapping = $batch->mapping() ?? throw new \LogicException('Validated batch lost its mapping.');
        $total = $valid = $invalid = 0;

        try {
            $stream = $this->storage->open($organization->requireId(), $batch->storageKey());
            try {
                $inspection = $this->csv->inspect($stream);
                foreach ($this->csv->rows($stream, $inspection) as $csvRow) {
                    ++$total;
                    $canonical = [];
                    foreach ($mapping as $header => $field) {
                        $canonical[$field] = $csvRow->data[$header] ?? null;
                    }
                    $row = ImportRow::create($batch, $csvRow->number, $csvRow->data, new \DateTimeImmutable());
                    try {
                        $result = $this->validators->get($batch->type())->validate($canonical, $organization);
                        $warnings = $this->formulaWarnings($canonical);
                        $row->markValid($result->normalized, $result->action, new \DateTimeImmutable(), [...$result->errors, ...$warnings]);
                        ++$valid;
                    } catch (DomainException $exception) {
                        $error = ['code' => $exception->errorCode(), 'message' => $exception->getMessage()];
                        if (null !== $exception->field()) {
                            $error['field'] = $exception->field();
                        }
                        $row->markInvalid([$error], $canonical, new \DateTimeImmutable());
                        ++$invalid;
                    } catch (\InvalidArgumentException $exception) {
                        $row->markInvalid([['code' => 'IMPORT_ROW_VALIDATION_FAILED', 'message' => $exception->getMessage()]], $canonical, new \DateTimeImmutable());
                        ++$invalid;
                    }
                    $this->rows->save($organization, $row);
                }
                if (0 === $total) {
                    throw new DomainException('IMPORT_EMPTY_FILE', 'O CSV deve possuir ao menos uma linha de dados.', 422, 'file');
                }
            } finally {
                fclose($stream);
            }

            $finished = new \DateTimeImmutable();
            $batch->finishValidation($total, $valid, $invalid, $finished);
            $this->audit->record($organization, $user, 'IMPORT_VALIDATED', 'ImportBatch', $batch->requireId(), null, ['status' => $batch->status()->value], ['total_rows' => $total, 'valid_rows' => $valid, 'error_rows' => $invalid], $finished);
            $this->batches->save($organization, $batch);

            return ImportBatchOutput::fromEntity($batch);
        } catch (\Throwable $exception) {
            $code = $exception instanceof DomainException ? $exception->errorCode() : 'IMPORT_VALIDATION_FAILED';
            try {
                $this->transactions->transactional(function () use ($organization, $user, $id, $code): void {
                    $failedBatch = $this->batches->findByIdForUpdate($organization, $id);
                    if (null === $failedBatch || $failedBatch->status()->terminal()) {
                        return;
                    }
                    $failedAt = new \DateTimeImmutable();
                    $this->rows->deleteByBatch($organization, $failedBatch);
                    $failedBatch->fail($code, $failedAt);
                    $this->audit->record($organization, $user, 'IMPORT_FAILED', 'ImportBatch', $failedBatch->requireId(), null, ['status' => $failedBatch->status()->value], ['failure_code' => $code], $failedAt);
                    $this->batches->save($organization, $failedBatch);
                });
            } catch (\Throwable) {
                // Preserve the original failure if the database itself is unavailable.
            }

            throw $exception;
        }
    }

    /**
     * @param array<string, string|null> $data
     *
     * @return list<array{code: string, field: string, message: string, severity: string}>
     */
    private function formulaWarnings(array $data): array
    {
        $warnings = [];
        foreach ($data as $field => $value) {
            if (null !== $value && 1 === preg_match('/^[=+\-@]/', ltrim($value))) {
                $warnings[] = ['code' => 'IMPORT_FORMULA_LIKE_TEXT', 'field' => $field, 'message' => 'Conteúdo preservado como texto; neutralize antes de exportar para planilha.', 'severity' => 'WARNING'];
            }
        }

        return $warnings;
    }
}
