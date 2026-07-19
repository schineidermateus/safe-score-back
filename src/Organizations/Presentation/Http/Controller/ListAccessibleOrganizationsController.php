<?php

declare(strict_types=1);

namespace App\Organizations\Presentation\Http\Controller;

use App\Organizations\Application\UseCase\ListAccessibleOrganizations;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListAccessibleOrganizationsController
{
    public function __construct(private ListAccessibleOrganizations $organizations)
    {
    }

    #[Route('/organizations', name: 'organizations_accessible', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return ApiResponseFactory::success($this->organizations->execute());
    }
}
