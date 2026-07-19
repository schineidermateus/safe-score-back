<?php

declare(strict_types=1);

namespace App\Tests\Organizations\Domain\Entity;

use App\Organizations\Domain\Entity\Organization;
use PHPUnit\Framework\TestCase;

final class OrganizationTest extends TestCase
{
    public function testItUsesMvpDefaultsAndStartsWithoutDatabaseId(): void
    {
        $organization = Organization::create(
            'Stone Traceability LTDA',
            'Stone Traceability',
            '04.252.011/0001-10',
            new \DateTimeImmutable(),
        );

        self::assertNull($organization->id());
        self::assertSame('04252011000110', $organization->document());
        self::assertSame('America/Sao_Paulo', $organization->timezone());
        self::assertSame('BRL', $organization->currency());
    }
}
