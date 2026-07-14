<?php

declare(strict_types=1);

namespace App\Organizations\Presentation\Http\Controller;

use App\Organizations\Application\UseCase\ListOrganizationMembers;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ListOrganizationMembersController
{
    public function __construct(private ListOrganizationMembers $listMembers)
    {
    }

    #[Route('/api/v1/organizations/current/members', name: 'organizations_members_list', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return ApiResponseFactory::success(array_map(
            static fn ($membership): array => $membership->toArray(),
            $this->listMembers->execute(),
        ));
    }
}
