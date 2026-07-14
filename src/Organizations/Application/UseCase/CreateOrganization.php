<?php

declare(strict_types=1);

namespace App\Organizations\Application\UseCase;

use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\DTO\CreateOrganizationInput;
use App\Organizations\Application\DTO\OrganizationOutput;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;
use App\Organizations\Domain\Repository\OrganizationRepository;
use App\Shared\Domain\Exception\DomainException;

final readonly class CreateOrganization
{
    public function __construct(
        private OrganizationRepository $organizations,
        private OrganizationMembershipRepository $memberships,
        private CurrentUserProviderInterface $currentUser,
    ) {
    }

    public function execute(CreateOrganizationInput $input): OrganizationOutput
    {
        $organization = Organization::create(
            $input->legalName,
            $input->tradeName,
            $input->document,
            new \DateTimeImmutable(),
            $input->timezone,
            $input->currency,
        );

        if (null !== $organization->document() && null !== $this->organizations->findByDocument($organization->document())) {
            throw new DomainException('ORGANIZATION_DOCUMENT_ALREADY_EXISTS', 'Já existe uma organização com este documento.', 409, 'document');
        }

        $this->organizations->save($organization);
        $membership = OrganizationMembership::join(
            $organization,
            $this->currentUser->currentUser(),
            MembershipRole::Owner,
            new \DateTimeImmutable(),
        );
        $this->memberships->save($membership);

        return OrganizationOutput::fromEntity($organization);
    }
}
