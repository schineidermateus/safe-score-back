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
use App\Imports\Domain\Entity\ImportBatch;
use App\Imports\Domain\Enum\ImportType;
use App\Imports\Domain\Repository\ImportBatchRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class CreateImportBatch
{
    public function __construct(private ImportBatchRepository $batches, private ImportFileStorageInterface $storage, private CsvReaderInterface $csv, private CurrentOrganizationProviderInterface $currentOrganization, private CurrentUserProviderInterface $currentUser, private AuthorizationService $authorization, private AuditLogger $audit, private TransactionManagerInterface $transactions)
    {
    }

    public function execute(string $typeValue, string $temporaryPath, string $originalFileName): ImportBatchOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::ImportCreate);
        $type = ImportType::tryFrom(strtoupper($typeValue)) ?? throw new DomainException('IMPORT_INVALID_TYPE', 'Tipo de importação inválido.', 422, 'type');
        $organization = $this->currentOrganization->currentOrganization();
        $user = $this->currentUser->currentUser();
        $stored = $this->storage->store($temporaryPath, $originalFileName);
        try {
            $stream = $this->storage->open($stored->storageKey);
            try {
                $inspection = $this->csv->inspect($stream);
            } finally {
                fclose($stream);
            }
            $duplicate = $this->batches->findCompletedByHash($organization, $type, $stored->hash);
            if (null !== $duplicate) {
                throw new DomainException('IMPORT_DUPLICATE_FILE', sprintf('Conteúdo já processado no lote %d.', $duplicate->requireId()), 409, 'file');
            }
            $batch = $this->transactions->transactional(function () use ($organization, $user, $type, $stored, $inspection): ImportBatch {
                $now = new \DateTimeImmutable();
                $batch = ImportBatch::create($organization, $user, $type, $stored->fileName, $stored->originalFileName, $stored->storageKey, $stored->hash, $stored->size, $inspection->headers, $inspection->encoding, $inspection->delimiter, $now);
                $this->batches->save($organization, $batch);
                $metadata = ['type' => $type->value, 'original_file_name' => $stored->originalFileName, 'file_hash' => $stored->hash];
                $this->audit->record($organization, $user, 'IMPORT_CREATED', 'ImportBatch', $batch->requireId(), null, ['status' => $batch->status()->value], $metadata, $now);
                $this->audit->record($organization, $user, 'IMPORT_FILE_UPLOADED', 'ImportBatch', $batch->requireId(), null, ['status' => $batch->status()->value], $metadata, $now);
                $this->batches->save($organization, $batch);

                return $batch;
            });

            return ImportBatchOutput::fromEntity($batch);
        } catch (\Throwable $exception) {
            $this->storage->remove($stored->storageKey);
            throw $exception;
        }
    }
}
