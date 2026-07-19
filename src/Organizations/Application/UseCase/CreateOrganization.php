<?php

declare(strict_types=1);

namespace App\Organizations\Application\UseCase;

use App\Audit\Application\AuditLogger;
use App\Identity\Application\Context\CurrentUserProviderInterface;
use App\Organizations\Application\DTO\CreateOrganizationInput;
use App\Organizations\Application\DTO\OrganizationOutput;
use App\Organizations\Domain\Entity\Organization;
use App\Organizations\Domain\Entity\OrganizationMembership;
use App\Organizations\Domain\Enum\MembershipRole;
use App\Organizations\Domain\Repository\OrganizationMembershipRepository;
use App\Organizations\Domain\Repository\OrganizationRepository;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\Exception\DomainException;

final readonly class CreateOrganization
{
    public function __construct(
        private OrganizationRepository $organizations,
        private OrganizationMembershipRepository $memberships,
        private CurrentUserProviderInterface $currentUser,
        private TransactionManagerInterface $transactions,
        private AuditLogger $audit,
    ) {
    }

    public function execute(CreateOrganizationInput $input): OrganizationOutput
    {
        return $this->transactions->transactional(function () use ($input): OrganizationOutput {
            $now = new \DateTimeImmutable();
            $organization = Organization::create($input->legalName, $input->tradeName, $input->document, $now, $input->timezone, $input->currency);
            if (null !== $organization->document() && null !== $this->organizations->findByDocument($organization->document())) {
                throw new DomainException('ORGANIZATION_DOCUMENT_ALREADY_EXISTS', 'Já existe uma organização com este documento.', 409, 'document');
            }

            $this->organizations->save($organization);
            $membership = OrganizationMembership::join($organization, $this->currentUser->currentUser(), MembershipRole::Owner, $now);
            $this->memberships->save($membership);
            $this->audit->record($organization, $this->currentUser->currentUser(), 'ORGANIZATION_CREATED', 'Organization', $organization->requireId(), null, ['status' => $organization->status()->value], null, $now);

            return OrganizationOutput::fromEntity($organization);
        });
    }
}
