<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wawaiguntang\Numbering\Numbering;
use Wawaiguntang\Numbering\Storages\MemoryStorage;

class NumberingTest extends TestCase
{
    public function test_basic_pattern(): void
    {
        $numbering = new Numbering('PU/{romanMonth}/{year}/{sequence:3}');
        $numbering->setStorage(new MemoryStorage());
        
        $result = $numbering->generate();
        $this->assertMatchesRegularExpression('/PU\/V\/\d{4}\/001/', $result);
    }

    public function test_fluent_api(): void
    {
        $numbering = new Numbering();
        $result = $numbering
            ->prefix('IGD')
            ->date('Ymd-His')
            ->sequence(2)
            ->setCounter(fn() => 1)
            ->generate();
        
        $this->assertStringContainsString('IGD', $result);
        $this->assertStringContainsString('01', $result);
    }

    public function test_roman_sequence(): void
    {
        $numbering = new Numbering('RI/{romanDate}/{roman}');
        $numbering
            ->setCounter(fn() => 5)
            ->romanSequence();
        
        $result = $numbering->generate();
        $this->assertStringContainsString('V', $result); // Roman 5
    }

    public function test_custom_param(): void
    {
        $numbering = new Numbering('{prefix}{param:kodePoli}{Ym}{sequence:3}');
        $result = $numbering
            ->param('prefix', 'PU')
            ->param('kodePoli', 'A')
            ->setCounter(fn() => 1)
            ->generate();
        
        $this->assertStringContainsString('PUA', $result);
        $this->assertStringContainsString('001', $result);
    }

    public function test_uppercase_lowercase(): void
    {
        $numbering = new Numbering('test');
        
        $result = $numbering->uppercase()->generate();
        $this->assertEquals('TEST', $result);
        
        $result = $numbering->lowercase()->generate();
        $this->assertEquals('test', $result);
    }

    public function test_transform_callback(): void
    {
        $numbering = new Numbering('TEST123');
        $result = $numbering
            ->transform(fn($s) => str_replace('0', 'O', $s))
            ->generate();
        
        $this->assertEquals('TEST123', $result);
    }

    public function test_template_save_and_load(): void
    {
        Numbering::saveTemplate('poli_umum', [
            'pattern' => '{prefix}{param:kodePoli}{Ym}{sequence:3}',
            'params' => ['prefix' => 'PU', 'kodePoli' => 'A'],
            'sequence' => 3,
        ]);
        
        $numbering = Numbering::fromTemplate('poli_umum');
        $numbering->setCounter(fn() => 1);
        $result = $numbering->generate();
        
        $this->assertStringContainsString('PUA', $result);
    }

    public function test_reset_period(): void
    {
        $storage = new MemoryStorage();
        
        $numbering = new Numbering('TEST/{sequence:3}');
        $numbering->setStorage($storage)->reset('daily');
        
        $result1 = $numbering->generate();
        $result2 = $numbering->generate();
        
        $this->assertNotEquals($result1, $result2);
    }

    public function test_generate_array(): void
    {
        $numbering = new Numbering('PU/{sequence:3}');
        $numbering->setCounter(fn() => 1);
        
        $result = $numbering->generateArray();
        
        $this->assertArrayHasKey('number', $result);
        $this->assertArrayHasKey('pattern', $result);
        $this->assertArrayHasKey('params', $result);
        $this->assertArrayHasKey('date', $result);
    }

    public function test_counter_from_callback(): void
    {
        $counter = 0;
        $numbering = new Numbering('REG/{sequence:3}');
        $numbering->setCounter(function() use (&$counter) {
            return ++$counter;
        });
        
        $this->assertEquals('REG/001', $numbering->generate());
        $this->assertEquals('REG/002', $numbering->generate());
        $this->assertEquals('REG/003', $numbering->generate());
    }

    public function test_literal_separators(): void
    {
        $numbering = new Numbering('LAB/{day}/{romanMonth}/{romanYearShort}/{random:4}');
        $numbering->setCounter(fn() => 1);
        
        $result = $numbering->generate();
        $this->assertStringContainsString('LAB/', $result);
        $this->assertStringContainsString('/V/', $result);
    }
}
