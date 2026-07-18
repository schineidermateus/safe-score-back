<?php

declare(strict_types=1);

namespace App\Receivables\Presentation\Http\Controller;

use App\Receivables\Application\DTO\UpdateReceivableInput;
use App\Receivables\Application\UseCase\UpdateReceivable;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateReceivableController
{
    public function __construct(private UpdateReceivable $useCase)
    {
    }

    #[Route('/api/v1/receivables/{id}', name: 'receivables_update', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function __invoke(int $id, #[MapRequestPayload(serializationContext: ['allow_extra_attributes' => false])] UpdateReceivableInput $input): JsonResponse
    {
        return ApiResponseFactory::success($this->useCase->execute($id, $input)->toArray());
    }
}
