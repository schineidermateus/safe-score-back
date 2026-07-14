<?php

declare(strict_types=1);

namespace App\Organizations\Presentation\Http\Controller;

use App\Organizations\Application\UseCase\GetCurrentOrganization;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class GetCurrentOrganizationController
{
    public function __construct(private GetCurrentOrganization $getCurrentOrganization)
    {
    }

    #[Route('/api/v1/organizations/current', name: 'organizations_current', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return ApiResponseFactory::success($this->getCurrentOrganization->execute()->toArray());
    }
}
