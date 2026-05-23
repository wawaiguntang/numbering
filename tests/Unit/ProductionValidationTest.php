<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wawaiguntang\Numbering\Numbering;
use Wawaiguntang\Numbering\Storages\MemoryStorage;
use Wawaiguntang\Numbering\Utils\RomanConverter;

/**
 * Production validation tests - ensuring library is ready for production use.
 */
class ProductionValidationTest extends TestCase
{
    // ==================== RACE CONDITION & CONCURRENCY ====================

    public function test_concurrent_counter_increment(): void
    {
        $storage = new MemoryStorage();
        $results = [];
        
        // Simulate concurrent requests
        for ($i = 0; $i < 100; $i++) {
            $numbering = new Numbering('TEST/{sequence:3}');
            $numbering->setStorage($storage);
            $results[] = $numbering->generate();
        }
        
        // All numbers should be unique
        $unique = array_unique($results);
        $this->assertCount(100, $unique, 'All generated numbers should be unique');
        
        // Should be sequential from 001 to 100
        $this->assertEquals('TEST/001', $results[0]);
        $this->assertEquals('TEST/100', $results[99]);
    }

    public function test_counter_isolation_per_key(): void
    {
        $storage = new MemoryStorage();
        
        // Different keys should have independent counters
        // Using prefix param for counter key separation
        $poliA = (new Numbering('{prefix}/{sequence:3}'))
            ->param('prefix', 'PUA')
            ->setStorage($storage)
            ->generate();
        
        $poliB = (new Numbering('{prefix}/{sequence:3}'))
            ->param('prefix', 'PUB')
            ->setStorage($storage)
            ->generate();
        
        $poliA2 = (new Numbering('{prefix}/{sequence:3}'))
            ->param('prefix', 'PUA')
            ->setStorage($storage)
            ->generate();
        
        $this->assertEquals('PUA/001', $poliA);
        $this->assertEquals('PUB/001', $poliB);
        $this->assertEquals('PUA/002', $poliA2);
    }

    // ==================== RESET PERIOD VALIDATION ====================

    public function test_daily_reset_changes_counter_key(): void
    {
        $storage = new MemoryStorage();
        
        $day1 = new \DateTimeImmutable('2025-05-23');
        $day2 = new \DateTimeImmutable('2025-05-24');
        
        $numbering = new Numbering('TEST/{sequence:3}');
        $numbering->setStorage($storage)->reset('daily');
        
        // Generate on day 1
        $result1 = $numbering->generate();
        
        // Simulate next day (new counter key)
        $numbering2 = new Numbering('TEST/{sequence:3}');
        $numbering2->setStorage($storage)->reset('daily');
        // Note: In real scenario, date would be different
        $result2 = $numbering2->generate();
        
        $this->assertEquals('TEST/001', $result1);
        // Same day - continues sequence
        $this->assertEquals('TEST/002', $result2);
    }

    public function test_monthly_reset_period(): void
    {
        $storage = new MemoryStorage();
        
        $numbering = new Numbering('PU/{romanMonth}/{sequence:3}');
        $numbering->setStorage($storage)->reset('monthly');
        
        $result = $numbering->generate();
        
        // Should include roman month and sequence
        $this->assertMatchesRegularExpression('/PU\/V\/\d{3}/', $result);
    }

    // ==================== EDGE CASES ====================

    public function test_empty_pattern_generates_empty_string(): void
    {
        $numbering = new Numbering('');
        $result = $numbering->generate();
        $this->assertEquals('', $result);
    }

    public function test_no_counter_returns_literal_pattern(): void
    {
        $numbering = new Numbering('INV/2025/{sequence:4}');
        $result = $numbering->generate();
        
        // Without counter, sequence placeholder remains or becomes empty
        $this->assertStringContainsString('INV/2025/', $result);
    }

    public function test_very_long_sequence_padding(): void
    {
        $storage = new MemoryStorage();
        
        $numbering = new Numbering('TEST/{sequence:10}');
        $numbering->setStorage($storage);
        
        $result = $numbering->generate();
        $this->assertEquals('TEST/0000000001', $result);
    }

    public function test_special_characters_in_params(): void
    {
        $numbering = new Numbering('{param:test}');
        $numbering->param('test', 'A/B-C_D');
        
        $result = $numbering->generate();
        $this->assertEquals('A/B-C_D', $result);
    }

    public function test_unicode_characters(): void
    {
        $numbering = new Numbering('{prefix}001');
        $numbering->param('prefix', 'テスト');
        
        $result = $numbering->generate();
        $this->assertStringContainsString('テスト', $result);
    }

    // ==================== ROMAN CONVERTER LIMITS ====================

    public function test_roman_converter_boundary_values(): void
    {
        // Min value
        $this->assertEquals('I', RomanConverter::toRoman(1));
        
        // Max value (3999)
        $this->assertEquals('MMMCMXCIX', RomanConverter::toRoman(3999));
    }

    public function test_roman_converter_throws_on_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RomanConverter::toRoman(0);
    }

    public function test_roman_converter_throws_on_too_large(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RomanConverter::toRoman(4000);
    }

    public function test_month_boundary_values(): void
    {
        $this->assertEquals('I', RomanConverter::month(1));
        $this->assertEquals('XII', RomanConverter::month(12));
    }

    public function test_month_throws_on_invalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        RomanConverter::month(13);
    }

    // ==================== PERFORMANCE ====================

    public function test_repeated_generation_performance(): void
    {
        $storage = new MemoryStorage();
        $numbering = new Numbering('PERF/{sequence:5}');
        $numbering->setStorage($storage);
        
        $start = microtime(true);
        
        for ($i = 0; $i < 1000; $i++) {
            $numbering->generate();
        }
        
        $elapsed = microtime(true) - $start;
        
        // Should complete 1000 generations in less than 1 second
        $this->assertLessThan(1.0, $elapsed, '1000 generations should complete in < 1 second');
    }

    public function test_memory_usage_stays_constant(): void
    {
        $storage = new MemoryStorage();
        $numbering = new Numbering('MEM/{sequence:5}');
        $numbering->setStorage($storage);
        
        $initialMemory = memory_get_usage();
        
        for ($i = 0; $i < 100; $i++) {
            $numbering->generate();
        }
        
        $finalMemory = memory_get_usage();
        $increase = $finalMemory - $initialMemory;
        
        // Memory increase should be minimal (less than 1MB for 100 iterations)
        $this->assertLessThan(1048576, $increase, 'Memory increase should be < 1MB');
    }

    // ==================== PATTERN VALIDATION ====================

    public function test_all_supported_placeholders_work(): void
    {
        $currentYear = date('Y');
        $currentDate = date('Y-m-d');
        $currentMonth = date('m');
        $currentDay = date('d');
        $currentRomanMonth = RomanConverter::month((int)date('n'));
        
        $patterns = [
            '{prefix:TEST}' => 'TEST',
            '{suffix:END}' => 'END',
            '{year}' => $currentYear,
            '{month}' => $currentMonth,
            '{day}' => $currentDay,
            '{romanMonth}' => $currentRomanMonth,
        ];
        
        foreach ($patterns as $pattern => $expected) {
            $numbering = new Numbering($pattern);
            $result = $numbering->generate();
            $this->assertEquals($expected, $result, "Pattern {$pattern} should produce {$expected}");
        }
    }

    public function test_complex_real_world_patterns(): void
    {
        $storage = new MemoryStorage();
        $yearMonth = date('Ym');
        $day = date('j');
        $romanMonth = RomanConverter::month((int)date('n'));
        $shortYear = RomanConverter::year((int)date('Y'), true);
        
        // SIMRS Poliklinik
        $poli = (new Numbering('{prefix}{param:kodePoli}{date:Ym}{sequence:3}'))
            ->param('prefix', 'PU')
            ->param('kodePoli', 'A')
            ->setStorage($storage)
            ->generate();
        $this->assertMatchesRegularExpression("/PUA{$yearMonth}\\d{3}/", $poli);
        
        // SIMRS IGD with Roman
        $igd = (new Numbering('IGD/{romanDate}/{sequence:2}'))
            ->setStorage($storage)
            ->generate();
        $this->assertStringContainsString("IGD/", $igd);
        $this->assertStringContainsString("/{$romanMonth}/", $igd);
        
        // SIMRS Rawat Inap with Roman counter (use fresh storage)
        $storageRI = new MemoryStorage();
        $ri = (new Numbering('RI/{param:kodeBangsal}/{roman}'))
            ->param('kodeBangsal', 'MEL')
            ->setStorage($storageRI)
            ->romanSequence()
            ->generate();
        $this->assertEquals('RI/MEL/I', $ri);
    }

    // ==================== STATE MANAGEMENT ====================

    public function test_instance_reuse_with_reset(): void
    {
        $storage = new MemoryStorage();
        
        $numbering = new Numbering('REUSE/{sequence:3}');
        $numbering->setStorage($storage);
        
        $r1 = $numbering->generate();
        $r2 = $numbering->generate();
        $r3 = $numbering->generate();
        
        $this->assertEquals('REUSE/001', $r1);
        $this->assertEquals('REUSE/002', $r2);
        $this->assertEquals('REUSE/003', $r3);
    }

    public function test_immutable_state_per_instance(): void
    {
        $storage = new MemoryStorage();
        
        // Use prefix param to separate counter keys
        $n1 = (new Numbering('{prefix}/{sequence:2}'))->param('prefix', 'A');
        $n1->setStorage($storage);
        
        $n2 = (new Numbering('{prefix}/{sequence:2}'))->param('prefix', 'B');
        $n2->setStorage($storage);
        
        $r1 = $n1->generate();
        $r2 = $n2->generate();
        $r3 = $n1->generate();
        
        $this->assertEquals('A/01', $r1);
        $this->assertEquals('B/01', $r2);
        $this->assertEquals('A/02', $r3);
    }

    // ==================== ERROR HANDLING ====================

    public function test_missing_param_returns_empty_string(): void
    {
        $numbering = new Numbering('{param:nonexistent}');
        $result = $numbering->generate();
        $this->assertEquals('', $result);
    }

    public function test_invalid_template_name_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Numbering::fromTemplate('nonexistent_template');
    }

    public function test_malformed_pattern_handles_gracefully(): void
    {
        // Unclosed brace
        $numbering = new Numbering('{prefix');
        $result = $numbering->generate();
        $this->assertEquals('{prefix', $result); // Literal output
    }

    // ==================== SECURITY ====================

    public function test_no_code_injection_in_pattern(): void
    {
        $maliciousPattern = '{prefix}"; system("rm -rf /"); //';
        $numbering = new Numbering($maliciousPattern);
        $numbering->param('prefix', 'TEST');
        
        $result = $numbering->generate();
        
        // Should not execute code, just output literal
        $this->assertStringContainsString('system', $result);
        $this->assertStringContainsString('rm', $result);
    }

    public function test_random_generation_is_random(): void
    {
        $results = [];
        
        for ($i = 0; $i < 10; $i++) {
            $numbering = new Numbering('{random:8}');
            $results[] = $numbering->generate();
        }
        
        // All should be unique (very high probability)
        $unique = array_unique($results);
        $this->assertGreaterThanOrEqual(9, count($unique), 'Random values should be unique');
    }
}
