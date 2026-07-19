<?php

declare(strict_types=1);

namespace App\Identity\Presentation\Http\Controller;

use App\Identity\Application\UseCase\GetCurrentUser;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetMeController
{
    public function __construct(private GetCurrentUser $getCurrentUser)
    {
    }

    #[Route('/auth/me', name: 'auth_me', methods: ['GET'])]
    #[Route('/api/v1/me', name: 'identity_me', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return ApiResponseFactory::success($this->getCurrentUser->execute());
    }
}
