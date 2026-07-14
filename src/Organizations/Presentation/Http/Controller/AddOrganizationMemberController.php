<?php

declare(strict_types=1);

namespace App\Organizations\Presentation\Http\Controller;

use App\Organizations\Application\DTO\AddMemberInput;
use App\Organizations\Application\UseCase\AddUserToOrganization;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class AddOrganizationMemberController
{
    public function __construct(private AddUserToOrganization $addMember)
    {
    }

    #[Route('/api/v1/organizations/current/members', name: 'organizations_members_add', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] AddMemberInput $input): JsonResponse
    {
        return ApiResponseFactory::success($this->addMember->execute($input)->toArray(), status: 201);
    }
}
