<?php

declare(strict_types=1);

namespace App\Receivables\Presentation\Http\Controller;

use App\Receivables\Application\DTO\CancelReceivableInput;
use App\Receivables\Application\UseCase\CancelReceivable;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CancelReceivableController
{
    public function __construct(private CancelReceivable $useCase)
    {
    }

    #[Route('/api/v1/receivables/{id}/cancel', name: 'receivables_cancel', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function __invoke(int $id, #[MapRequestPayload(serializationContext: ['allow_extra_attributes' => false])] CancelReceivableInput $input): JsonResponse
    {
        return ApiResponseFactory::success($this->useCase->execute($id, $input)->toArray());
    }
}
