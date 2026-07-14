<?php

declare(strict_types=1);

namespace App\Organizations\Domain\Repository;

use App\Organizations\Domain\Entity\Organization;

interface OrganizationRepository
{
    public function save(Organization $organization): void;

    public function findById(int $id): ?Organization;

    public function findByDocument(string $document): ?Organization;
}
