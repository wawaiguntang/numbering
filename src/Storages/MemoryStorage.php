<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering\Storages;

use Wawaiguntang\Numbering\Contracts\CounterStorageInterface;

/**
 * In-memory counter storage for testing.
 */
class MemoryStorage implements CounterStorageInterface
{
    private array $counters = [];

    public function next(string $key, array $context = []): int
    {
        if (!isset($this->counters[$key])) {
            $this->counters[$key] = 0;
        }
        
        return ++$this->counters[$key];
    }

    /**
     * Get current value without incrementing.
     */
    public function current(string $key): int
    {
        return $this->counters[$key] ?? 0;
    }

    /**
     * Reset a counter.
     */
    public function reset(string $key): void
    {
        unset($this->counters[$key]);
    }

    /**
     * Reset all counters.
     */
    public function resetAll(): void
    {
        $this->counters = [];
    }
}
