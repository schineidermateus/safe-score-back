<?php

declare(strict_types=1);

namespace App\Receivables\Presentation\Http\Controller;

use App\Receivables\Application\DTO\RegisterReceivablePaymentInput;
use App\Receivables\Application\UseCase\RegisterReceivablePayment;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class RegisterReceivablePaymentController
{
    public function __construct(private RegisterReceivablePayment $useCase)
    {
    }

    #[Route('/api/v1/receivables/{id}/payments', name: 'receivables_payment_register', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function __invoke(int $id, #[MapRequestPayload(serializationContext: ['allow_extra_attributes' => false])] RegisterReceivablePaymentInput $input): JsonResponse
    {
        return ApiResponseFactory::success($this->useCase->execute($id, $input)->toArray(), status: 201);
    }
}
