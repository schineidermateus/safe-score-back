<?php

declare(strict_types=1);

namespace App\Imports\Presentation\Http\Controller;

use App\Imports\Application\UseCase\CreateImportBatch;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CreateImportBatchController
{
    public function __construct(private CreateImportBatch $create)
    {
    }

    #[Route('/api/v1/imports', name: 'imports_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile || !$file->isValid()) {
            throw new DomainException('IMPORT_INVALID_FILE', 'Envie um arquivo CSV válido no campo file.', 422, 'file');
        }
        $type = (string) $request->request->get('type', '');
        $output = $this->create->execute($type, $file->getPathname(), $file->getClientOriginalName());

        return ApiResponseFactory::success($output->toArray(), status: 201);
    }
}
