<?php

declare(strict_types=1);

namespace App\Imports\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Imports\Application\DTO\ImportBatchOutput;
use App\Imports\Domain\Enum\ImportBatchStatus;
use App\Imports\Domain\Enum\ImportType;
use App\Imports\Domain\Repository\ImportBatchRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class ListImportBatches
{
    public function __construct(private ImportBatchRepository $batches, private CurrentOrganizationProviderInterface $currentOrganization, private AuthorizationService $authorization)
    {
    }

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int} */
    public function execute(?string $type, ?string $status, int $page, int $perPage): array
    {
        $this->authorization->assertGranted(AuthorizationAction::ImportRead);
        $organization = $this->currentOrganization->currentOrganization();
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));
        $importType = null === $type || '' === $type ? null : ImportType::tryFrom(strtoupper($type));
        $batchStatus = null === $status || '' === $status ? null : ImportBatchStatus::tryFrom(strtoupper($status));
        if (null !== $type && '' !== $type && null === $importType) {
            throw new DomainException('IMPORT_INVALID_TYPE', 'Filtro type inválido.', 422, 'type');
        }
        if (null !== $status && '' !== $status && null === $batchStatus) {
            throw new DomainException('IMPORT_INVALID_STATUS', 'Filtro status inválido.', 422, 'status');
        }
        $items = array_map(static fn ($batch): array => ImportBatchOutput::fromEntity($batch)->toArray(), $this->batches->list($organization, $importType, $batchStatus, $page, $perPage));

        return ['items' => $items, 'total' => $this->batches->countMatching($organization, $importType, $batchStatus), 'page' => $page, 'per_page' => $perPage];
    }
}
