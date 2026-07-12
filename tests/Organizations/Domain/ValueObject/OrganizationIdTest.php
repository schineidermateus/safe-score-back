<?php

declare(strict_types=1);

namespace App\Tests\Organizations\Domain\ValueObject;

use App\Organizations\Domain\ValueObject\OrganizationId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OrganizationIdTest extends TestCase
{
    public function testItNormalizesAndComparesIdentifiers(): void
    {
        $organizationId = new OrganizationId(' organization-1 ');

        self::assertSame('organization-1', (string) $organizationId);
        self::assertTrue($organizationId->equals(new OrganizationId('organization-1')));
    }

    #[DataProvider('invalidIdentifiers')]
    public function testItRejectsInvalidIdentifiers(string $value): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new OrganizationId($value);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIdentifiers(): iterable
    {
        yield 'empty' => [''];
        yield 'blank' => ['   '];
        yield 'too long' => [str_repeat('a', 65)];
    }
}
