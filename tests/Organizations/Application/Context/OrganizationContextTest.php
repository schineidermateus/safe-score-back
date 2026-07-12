<?php

declare(strict_types=1);

namespace App\Tests\Organizations\Application\Context;

use App\Organizations\Application\Context\OrganizationContext;
use App\Organizations\Domain\ValueObject\OrganizationId;
use App\Shared\Domain\Exception\DomainException;
use PHPUnit\Framework\TestCase;

final class OrganizationContextTest extends TestCase
{
    public function testItExposesAndClearsTheActiveOrganization(): void
    {
        $context = new OrganizationContext();
        $organizationId = new OrganizationId('organization-1');

        $context->set($organizationId);

        self::assertTrue($organizationId->equals($context->requireOrganizationId()));

        $context->clear();

        self::assertNull($context->organizationId());
    }

    public function testItRequiresAnActiveOrganization(): void
    {
        $context = new OrganizationContext();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Nenhuma organização ativa está disponível.');

        $context->requireOrganizationId();
    }
}
