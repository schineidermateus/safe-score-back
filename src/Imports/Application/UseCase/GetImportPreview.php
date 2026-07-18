<?php

declare(strict_types=1);

namespace App\Imports\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Imports\Application\DTO\ImportRowOutput;
use App\Imports\Domain\Repository\ImportBatchRepository;
use App\Imports\Domain\Repository\ImportRowRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class GetImportPreview
{
    public function __construct(private ImportBatchRepository $batches, private ImportRowRepository $rows, private CurrentOrganizationProviderInterface $currentOrganization, private AuthorizationService $authorization)
    {
    }

    /** @return array{items: list<array<string, mixed>>, total: int, page: int, per_page: int} */
    public function execute(int $id, int $page, int $perPage): array
    {
        $this->authorization->assertGranted(AuthorizationAction::ImportRead);
        $organization = $this->currentOrganization->currentOrganization();
        $batch = $this->batches->findById($organization, $id) ?? throw new DomainException('IMPORT_NOT_FOUND', 'Lote de importação não encontrado.', 404);
        $batch->assertPreviewAvailable();
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        return ['items' => array_map(static fn ($row): array => ImportRowOutput::fromEntity($row)->toArray(), $this->rows->list($organization, $batch, $page, $perPage)), 'total' => $this->rows->countMatching($organization, $batch), 'page' => $page, 'per_page' => $perPage];
    }
}
