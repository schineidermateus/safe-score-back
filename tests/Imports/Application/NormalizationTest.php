<?php

declare(strict_types=1);

namespace App\Tests\Imports\Application;

use App\Imports\Application\Normalization\DateNormalizer;
use App\Imports\Application\Normalization\MoneyNormalizer;
use App\Imports\Application\Normalization\TextNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NormalizationTest extends TestCase
{
    #[DataProvider('moneyValues')]
    public function testMoneyFormats(string $input, string $expected): void
    {
        self::assertSame($expected, (new MoneyNormalizer())->normalize($input, 'amount'));
    }

    /** @return iterable<string, array{string, string}> */
    public static function moneyValues(): iterable
    {
        yield 'pt-BR grouped' => ['R$ 1.234,56', '1234.56'];
        yield 'pt-BR plain' => ['1234,5', '1234.50'];
        yield 'canonical' => ['1234.56', '1234.56'];
        yield 'leading zeros' => ['0001,20', '1.20'];
    }

    public function testAmbiguousMoneyIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new MoneyNormalizer())->normalize('1.234', 'amount');
    }

    public function testDatesUseOnlyApprovedFormats(): void
    {
        $normalizer = new DateNormalizer();
        self::assertSame('2026-07-15', $normalizer->required('15/07/2026', 'date')->format('Y-m-d'));
        self::assertSame('2026-07-15', $normalizer->required('2026-07-15', 'date')->format('Y-m-d'));
    }

    public function testAmbiguousDateIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new DateNormalizer())->required('01/02/03', 'date');
    }

    public function testTextPreservesAccentsAndTrims(): void
    {
        self::assertSame('Comércio São João', (new TextNormalizer())->required('  Comércio São João  ', 100, 'name'));
    }
}
