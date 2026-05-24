<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wawaiguntang\Numbering\AdvancedNumbering;
use Wawaiguntang\Numbering\Storages\AdvancedStorage;

class AdvancedNumberingTest extends TestCase
{
    public function test_reset_every(): void
    {
        $counter = 0;
        $storage = new AdvancedStorage(function() use (&$counter) {
            return ++$counter;
        });
        
        $numbering = new AdvancedNumbering('TEST/{sequence:3}');
        $numbering
            ->setAdvancedStorage($storage)
            ->resetEvery(5);

        // Should reset after 5
        $this->assertEquals('TEST/001', $numbering->generate());
        $this->assertEquals('TEST/002', $numbering->generate());
        $this->assertEquals('TEST/003', $numbering->generate());
        $this->assertEquals('TEST/004', $numbering->generate());
        $this->assertEquals('TEST/005', $numbering->generate());
        $this->assertEquals('TEST/001', $numbering->generate()); // Reset
    }

    public function test_max_limit_throw(): void
    {
        $counter = 0;
        $storage = new AdvancedStorage(function() use (&$counter) {
            return ++$counter;
        });
        
        $numbering = new AdvancedNumbering('TEST/{sequence:2}');
        $numbering
            ->setAdvancedStorage($storage)
            ->maxLimit(3, 'throw');

        $this->assertEquals('TEST/01', $numbering->generate());
        $this->assertEquals('TEST/02', $numbering->generate());
        $this->assertEquals('TEST/03', $numbering->generate());

        $this->expectException(\RuntimeException::class);
        $numbering->generate();
    }

    public function test_max_limit_reset(): void
    {
        $counter = 0;
        $storage = new AdvancedStorage(function() use (&$counter) {
            return ++$counter;
        });
        
        $numbering = new AdvancedNumbering('TEST/{sequence:2}');
        $numbering
            ->setAdvancedStorage($storage)
            ->maxLimit(3, 'reset');

        $this->assertEquals('TEST/01', $numbering->generate());
        $this->assertEquals('TEST/02', $numbering->generate());
        $this->assertEquals('TEST/03', $numbering->generate());
        $this->assertEquals('TEST/01', $numbering->generate()); // Reset
    }

    public function test_skip_numbers(): void
    {
        $counter = 0;
        $storage = new AdvancedStorage(function() use (&$counter) {
            return ++$counter;
        });
        
        $numbering = new AdvancedNumbering('TEST/{sequence:2}');
        $numbering
            ->setAdvancedStorage($storage)
            ->skip([4, 5]);

        $this->assertEquals('TEST/01', $numbering->generate()); // 1
        $this->assertEquals('TEST/02', $numbering->generate()); // 2
        $this->assertEquals('TEST/03', $numbering->generate()); // 3
        $this->assertEquals('TEST/06', $numbering->generate()); // Skip 4,5 -> 6
    }

    public function test_pad_char(): void
    {
        $counter = 0;
        $storage = new AdvancedStorage(function() use (&$counter) {
            return ++$counter;
        });
        
        $numbering = new AdvancedNumbering('TEST/{sequence}');
        $numbering
            ->setAdvancedStorage($storage)
            ->sequence(3)
            ->padChar('X');

        $result = $numbering->generate();
        $this->assertStringContainsString('XX1', $result);
    }

    public function test_combined_features(): void
    {
        $counter = 0;
        $storage = new AdvancedStorage(function() use (&$counter) {
            return ++$counter;
        });
        
        $numbering = new AdvancedNumbering('PU/{date:Ym}/{sequence:3}');
        $numbering
            ->setAdvancedStorage($storage)
            ->resetEvery(100)
            ->skip([13]) // Skip unlucky number
            ->padChar('0');

        // Generate numbers
        $numbers = [];
        for ($i = 0; $i < 15; $i++) {
            $numbers[] = $numbering->generate();
        }

        // Check that 013 is not in the list (skipped)
        foreach ($numbers as $num) {
            $this->assertStringNotContainsString('013', $num);
        }
    }
}
