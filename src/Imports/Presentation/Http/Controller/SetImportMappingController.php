<?php

declare(strict_types=1);

namespace App\Imports\Presentation\Http\Controller;

use App\Imports\Application\UseCase\SetImportMapping;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final readonly class SetImportMappingController
{
    public function __construct(private SetImportMapping $setMapping)
    {
    }

    #[Route('/api/v1/imports/{id}/mapping', name: 'imports_mapping', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(int $id, Request $request): JsonResponse
    {
        $payload = $request->toArray();
        $mapping = $payload['mapping'] ?? null;
        if (!is_array($mapping)) {
            throw new DomainException('IMPORT_INVALID_MAPPING', 'mapping deve ser um objeto JSON.', 422, 'mapping');
        }
        $normalized = [];
        foreach ($mapping as $header => $field) {
            if (!is_string($header) || !is_string($field)) {
                throw new DomainException('IMPORT_INVALID_MAPPING', 'mapping deve relacionar textos.', 422, 'mapping');
            } $normalized[$header] = $field;
        }

        return ApiResponseFactory::success($this->setMapping->execute($id, $normalized)->toArray());
    }
}
