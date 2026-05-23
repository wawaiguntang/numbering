<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wawaiguntang\Numbering\Formatter;

class FormatterTest extends TestCase
{
    private Formatter $formatter;

    protected function setUp(): void
    {
        $this->formatter = new Formatter();
    }

    public function test_format_prefix(): void
    {
        $result = $this->formatter->format('{prefix}', null);
        $this->assertEquals('', $result);

        $result = $this->formatter->setParam('prefix', 'PU')->format('{prefix}', null);
        $this->assertEquals('PU', $result);

        $result = $this->formatter->format('{prefix:IGD}', null);
        $this->assertEquals('IGD', $result);
    }

    public function test_format_sequence(): void
    {
        $result = $this->formatter->setSequence(1)->format('{sequence}', null);
        $this->assertEquals('1', $result);

        $result = $this->formatter->setSequence(1, 3)->format('{sequence}', null);
        $this->assertEquals('001', $result);

        $result = $this->formatter->setSequence(42, 4)->format('{sequence}', null);
        $this->assertEquals('0042', $result);
    }

    public function test_format_roman_sequence(): void
    {
        $result = $this->formatter->setSequence(1)->useRomanSequence(true)->format('{sequence}', null);
        $this->assertEquals('I', $result);

        $result = $this->formatter->setSequence(5)->useRomanSequence(true)->format('{sequence}', null);
        $this->assertEquals('V', $result);
    }

    public function test_format_roman_placeholder(): void
    {
        $result = $this->formatter->setSequence(1)->format('{roman}', null);
        $this->assertEquals('I', $result);

        $result = $this->formatter->setSequence(10)->format('{roman}', null);
        $this->assertEquals('X', $result);
    }

    public function test_format_date(): void
    {
        $date = new \DateTimeImmutable('2025-05-23');
        
        $result = $this->formatter->format('{date}', $date);
        $this->assertEquals('20250523', $result);

        $result = $this->formatter->format('{date:Y-m-d}', $date);
        $this->assertEquals('2025-05-23', $result);

        $result = $this->formatter->format('{date:Ym}', $date);
        $this->assertEquals('202505', $result);
    }

    public function test_format_date_parts(): void
    {
        $date = new \DateTimeImmutable('2025-05-23');
        
        $result = $this->formatter->format('{year}', $date);
        $this->assertEquals('2025', $result);

        $result = $this->formatter->format('{month}', $date);
        $this->assertEquals('05', $result);

        $result = $this->formatter->format('{day}', $date);
        $this->assertEquals('23', $result);
    }

    public function test_format_roman_month(): void
    {
        $date = new \DateTimeImmutable('2025-05-23');
        
        $result = $this->formatter->format('{romanMonth}', $date);
        $this->assertEquals('V', $result);
    }

    public function test_format_roman_year(): void
    {
        $date = new \DateTimeImmutable('2025-05-23');
        
        $result = $this->formatter->format('{romanYear}', $date);
        $this->assertEquals('MMXXV', $result);

        $result = $this->formatter->format('{romanYearShort}', $date);
        $this->assertEquals('XXV', $result);
    }

    public function test_format_roman_date(): void
    {
        $date = new \DateTimeImmutable('2025-05-23');
        
        $result = $this->formatter->format('{romanDate}', $date);
        $this->assertEquals('XXIII/V/MMXXV', $result);

        $result = $this->formatter->format('{romanDateShort}', $date);
        $this->assertEquals('23/V/XXV', $result);
    }

    public function test_format_param(): void
    {
        $result = $this->formatter
            ->setParam('kodePoli', 'A')
            ->format('{prefix}{param:kodePoli}{sequence:3}', null);
        
        // prefix empty, kodePoli = A, sequence not set
        $this->assertStringContainsString('A', $result);
    }

    public function test_format_random(): void
    {
        $result = $this->formatter->format('{random}', null);
        $this->assertEquals(4, strlen($result)); // default 4 chars

        $result = $this->formatter->format('{random:6}', null);
        $this->assertEquals(6, strlen($result));
    }

    public function test_format_complex_pattern(): void
    {
        $date = new \DateTimeImmutable('2025-05-23');
        
        $result = $this->formatter
            ->setParam('prefix', 'PU')
            ->setSequence(1, 3)
            ->format('{prefix}/{romanMonth}/{year}/{sequence}', $date);
        
        $this->assertEquals('PU/V/2025/001', $result);
    }
}
