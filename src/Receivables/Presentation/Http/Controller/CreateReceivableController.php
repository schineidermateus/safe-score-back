<?php

declare(strict_types=1);

namespace App\Receivables\Presentation\Http\Controller;

use App\Receivables\Application\DTO\CreateReceivableInput;
use App\Receivables\Application\UseCase\CreateReceivable;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class CreateReceivableController
{
    public function __construct(private CreateReceivable $useCase)
    {
    }

    #[Route('/api/v1/receivables', name: 'receivables_create', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload(serializationContext: ['allow_extra_attributes' => false])] CreateReceivableInput $input): JsonResponse
    {
        return ApiResponseFactory::success($this->useCase->execute($input)->toArray(), status: 201);
    }
}
