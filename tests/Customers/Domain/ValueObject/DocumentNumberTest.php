<?php

declare(strict_types=1);

namespace App\Tests\Customers\Domain\ValueObject;

use App\Customers\Domain\ValueObject\DocumentNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DocumentNumberTest extends TestCase
{
    #[DataProvider('validDocuments')]
    public function testItNormalizesValidDocuments(string $input, string $expected): void
    {
        self::assertSame($expected, (string) new DocumentNumber($input));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function validDocuments(): iterable
    {
        yield 'cpf' => ['529.982.247-25', '52998224725'];
        yield 'cnpj' => ['04.252.011/0001-10', '04252011000110'];
    }

    #[DataProvider('invalidDocuments')]
    public function testItRejectsInvalidDocuments(string $document): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new DocumentNumber($document);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidDocuments(): iterable
    {
        yield 'invalid check digits' => ['12345678901'];
        yield 'repeated digits' => ['11111111111'];
        yield 'unsupported length' => ['123'];
    }
}
