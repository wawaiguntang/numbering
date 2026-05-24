<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wawaiguntang\Numbering\Storages\AdvancedStorage;

class AdvancedStorageTest extends TestCase
{
    private int $counter = 0;

    protected function setUp(): void
    {
        $this->counter = 0;
    }

    private function createStorage(): AdvancedStorage
    {
        return new AdvancedStorage(function() {
            return ++$this->counter;
        });
    }

    public function test_basic_counter(): void
    {
        $storage = $this->createStorage();

        $this->assertEquals(1, $storage->next('test', []));
        $this->assertEquals(2, $storage->next('test', []));
        $this->assertEquals(3, $storage->next('test', []));
    }

    public function test_reset_every(): void
    {
        $storage = $this->createStorage();
        $storage->resetEvery(5);

        // Should cycle 1-5 repeatedly
        $this->assertEquals(1, $storage->next('test', []));
        $this->assertEquals(2, $storage->next('test', []));
        $this->assertEquals(3, $storage->next('test', []));
        $this->assertEquals(4, $storage->next('test', []));
        $this->assertEquals(5, $storage->next('test', []));
        $this->assertEquals(1, $storage->next('test', [])); // Reset
        $this->assertEquals(2, $storage->next('test', []));
    }

    public function test_skip_numbers(): void
    {
        $storage = $this->createStorage();
        $storage->skip([3, 4]);

        $this->assertEquals(1, $storage->next('test', []));
        $this->assertEquals(2, $storage->next('test', []));
        // 3 and 4 are skipped -> 5
        $this->assertEquals(5, $storage->next('test', []));
        // Next: counter increments to 6
        $this->assertEquals(6, $storage->next('test', []));
    }

    public function test_max_limit_throw(): void
    {
        $counter = 0;
        $storage = new AdvancedStorage(function() use (&$counter) {
            return ++$counter;
        });
        $storage->maxLimit(3, 'throw');

        $this->assertEquals(1, $storage->next('test', []));
        $this->assertEquals(2, $storage->next('test', []));
        $this->assertEquals(3, $storage->next('test', []));

        $this->expectException(\RuntimeException::class);
        $storage->next('test', []);
    }

    public function test_max_limit_reset(): void
    {
        $counter = 0;
        $storage = new AdvancedStorage(function() use (&$counter) {
            return ++$counter;
        });
        $storage->maxLimit(3, 'reset');

        $this->assertEquals(1, $storage->next('test', []));
        $this->assertEquals(2, $storage->next('test', []));
        $this->assertEquals(3, $storage->next('test', []));
        $this->assertEquals(1, $storage->next('test', [])); // Reset
    }

    public function test_combined_reset_every_and_skip(): void
    {
        $counter = 0;
        $storage = new AdvancedStorage(function() use (&$counter) {
            return ++$counter;
        });
        $storage
            ->resetEvery(10)
            ->skip([5, 6]);

        $results = [];
        for ($i = 0; $i < 12; $i++) {
            $results[] = $storage->next('test', []);
        }

        // Check no 5 or 6 in results
        $this->assertNotContains(5, $results);
        $this->assertNotContains(6, $results);

        // Check reset happened
        $this->assertContains(1, array_slice($results, 8));
    }

    public function test_calculate_next(): void
    {
        $storage = new AdvancedStorage(function() {
            return 1;
        });
        $storage->skip([2, 3]);

        $this->assertEquals(4, $storage->calculateNext(1));
        $this->assertEquals(5, $storage->calculateNext(4));
    }
}
