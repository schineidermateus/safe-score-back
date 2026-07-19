<?php

declare(strict_types=1);

namespace App\Organizations\Presentation\Http\Controller;

use App\Organizations\Application\DTO\UpdateMembershipInput;
use App\Organizations\Application\UseCase\ChangeMembershipRole;
use App\Organizations\Application\UseCase\SuspendMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Shared\Domain\Exception\DomainException;
use App\Shared\Presentation\Http\ApiResponseFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final readonly class UpdateOrganizationMemberController
{
    public function __construct(
        private ChangeMembershipRole $changeRole,
        private SuspendMembership $suspendMembership,
    ) {
    }

    #[Route('/api/v1/organizations/current/members/{id}', name: 'organizations_members_update', requirements: ['id' => '\d+'], methods: ['PATCH'])]
    public function __invoke(int $id, #[MapRequestPayload] UpdateMembershipInput $input): JsonResponse
    {
        if (null !== $input->role) {
            return ApiResponseFactory::success(
                $this->changeRole->execute($id, MembershipRole::from($input->role))->toArray(),
            );
        }

        if ('SUSPENDED' === $input->status) {
            return ApiResponseFactory::success($this->suspendMembership->execute($id)->toArray());
        }

        throw new DomainException('INVALID_MEMBERSHIP_UPDATE', 'Informe role ou status para atualizar o vínculo.', 422);
    }
}
