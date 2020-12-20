<?php

declare(strict_types=1);

namespace Model\Cashbook\Cashbook;

use Codeception\Test\Unit;
use InvalidArgumentException;

use function strtoupper;

class ChitNumberTest extends Unit
{
    /**
     * @dataProvider getInvalidNumbers
     */
    public function testInvalidChitNumbersThrowException(string $value, string $reason): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ChitNumber($value);

        $this->fail('Exception for \'' . $reason . '\' not thrown');
    }

    /**
     * @dataProvider getValidNumbers
     */
    public function testValidNumbers(string $value): void
    {
        $number = new ChitNumber($value);

        $this->assertSame($value, $number->toString());
    }

    public function testToString(): void
    {
        $value = 'A123';

        $this->assertSame($value, (string) new ChitNumber($value));
    }

    public function testUpperCase(): void
    {
        $value = 'a123';

        $this->assertSame(strtoupper($value), (string) new ChitNumber($value));
    }

    /**
     * @return mixed[]
     */
    public function getInvalidNumbers(): array
    {
        return [
            ['123456', 'longer than 6 symbols'],
            ['A', 'letters only'],
            ['1A', 'letter postfix'],
            ['', 'empty number'],
            ['$1', 'non-alphanumeric symbol'],
            ['ABCD1', 'prefix longer than 3 symbols'],
            ['A/01', 'slash at the beginning of numberic part'],
            ['/A01', 'slash at the beginning of number'],
            ['A01/', 'slash at the end of number'],
        ];
    }

    /**
     * @return string[][]
     */
    public function getValidNumbers(): array
    {
        return [
            ['12345'],
            ['1'],
            ['ABC1'],
            ['A1'],
            ['0'],
            ['A0'],
            ['A1/1'],
        ];
    }
}
