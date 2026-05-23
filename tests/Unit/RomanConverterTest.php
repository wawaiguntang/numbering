<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wawaiguntang\Numbering\Utils\RomanConverter;

class RomanConverterTest extends TestCase
{
    public function test_to_roman_basic(): void
    {
        $this->assertEquals('I', RomanConverter::toRoman(1));
        $this->assertEquals('V', RomanConverter::toRoman(5));
        $this->assertEquals('X', RomanConverter::toRoman(10));
        $this->assertEquals('L', RomanConverter::toRoman(50));
        $this->assertEquals('C', RomanConverter::toRoman(100));
        $this->assertEquals('D', RomanConverter::toRoman(500));
        $this->assertEquals('M', RomanConverter::toRoman(1000));
    }

    public function test_to_roman_complex(): void
    {
        $this->assertEquals('IV', RomanConverter::toRoman(4));
        $this->assertEquals('IX', RomanConverter::toRoman(9));
        $this->assertEquals('XL', RomanConverter::toRoman(40));
        $this->assertEquals('XC', RomanConverter::toRoman(90));
        $this->assertEquals('CD', RomanConverter::toRoman(400));
        $this->assertEquals('CM', RomanConverter::toRoman(900));
    }

    public function test_to_roman_year_2025(): void
    {
        $this->assertEquals('MMXXV', RomanConverter::toRoman(2025));
    }

    public function test_to_roman_lowercase(): void
    {
        $this->assertEquals('mmxxv', RomanConverter::toRoman(2025, false));
    }

    public function test_month(): void
    {
        $this->assertEquals('I', RomanConverter::month(1));
        $this->assertEquals('V', RomanConverter::month(5));
        $this->assertEquals('XII', RomanConverter::month(12));
        $this->assertEquals('i', RomanConverter::month(1, false));
    }

    public function test_year(): void
    {
        $this->assertEquals('MMXXV', RomanConverter::year(2025));
        $this->assertEquals('XXV', RomanConverter::year(2025, true)); // short
    }

    public function test_from_roman(): void
    {
        $this->assertEquals(1, RomanConverter::fromRoman('I'));
        $this->assertEquals(5, RomanConverter::fromRoman('V'));
        $this->assertEquals(10, RomanConverter::fromRoman('X'));
        $this->assertEquals(2025, RomanConverter::fromRoman('MMXXV'));
    }

    public function test_out_of_range_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RomanConverter::toRoman(0);
    }
}
