<?php

declare(strict_types=1);

namespace App\Tests\Industrial\Domain;

use App\Industrial\Domain\Entity\BusinessPartner;
use App\Industrial\Domain\Entity\Machine;
use App\Industrial\Domain\Entity\Material;
use App\Industrial\Domain\Entity\Quarry;
use App\Industrial\Domain\Entity\StorageLocation;
use App\Industrial\Domain\Enum\BusinessPartnerType;
use App\Industrial\Domain\Enum\FoundationStatus;
use App\Organizations\Domain\Entity\Organization;
use PHPUnit\Framework\TestCase;

final class IndustrialFoundationTest extends TestCase
{
    public function testBusinessPartnerHasOnlyIndustrialFoundationDataAndTenant(): void
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization', null, null, $now);
        $partner = BusinessPartner::create($organization, 'SUP-001', 'Supplier Ltd', 'Supplier', BusinessPartnerType::Supplier, $now);

        self::assertSame($organization, $partner->organization());
        self::assertSame('SUP-001', $partner->code());
        self::assertSame(BusinessPartnerType::Supplier, $partner->type());
        self::assertSame(FoundationStatus::Active, $partner->status());
        self::assertNull($partner->id());
    }

    public function testEveryFoundationCatalogBelongsToOrganization(): void
    {
        $now = new \DateTimeImmutable();
        $organization = Organization::create('Organization', null, null, $now);
        $entities = [
            Material::create($organization, 'MAT-01', 'Material', $now),
            Quarry::create($organization, 'QUA-01', 'Quarry', $now),
            StorageLocation::create($organization, 'LOC-01', 'Location', $now),
            Machine::create($organization, 'MAC-01', 'Machine', $now),
        ];

        foreach ($entities as $entity) {
            self::assertSame($organization, $entity->organization());
            self::assertNull($entity->id());
        }
    }
}
