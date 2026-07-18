<?php

declare(strict_types=1);

namespace App\Imports\Application\UseCase;

use App\Authorization\Application\AuthorizationService;
use App\Authorization\Domain\AuthorizationAction;
use App\Imports\Application\DTO\ImportBatchOutput;
use App\Imports\Domain\Repository\ImportBatchRepository;
use App\Organizations\Application\Context\CurrentOrganizationProviderInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class GetImportBatch
{
    public function __construct(private ImportBatchRepository $batches, private CurrentOrganizationProviderInterface $currentOrganization, private AuthorizationService $authorization)
    {
    }

    public function execute(int $id): ImportBatchOutput
    {
        $this->authorization->assertGranted(AuthorizationAction::ImportRead);
        $organization = $this->currentOrganization->currentOrganization();

        return ImportBatchOutput::fromEntity($this->batches->findById($organization, $id) ?? throw new DomainException('IMPORT_NOT_FOUND', 'Lote de importação não encontrado.', 404));
    }
}
