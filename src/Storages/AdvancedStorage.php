<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering\Storages;

use Wawaiguntang\Numbering\Contracts\CounterStorageInterface;

/**
 * Advanced storage with reset every N and skip numbers support.
 */
class AdvancedStorage implements CounterStorageInterface
{
    private \Closure $callback;
    private ?int $resetEvery = null;
    private array $skipNumbers = [];
    private ?int $maxLimit = null;
    private string $onMaxReach = 'throw';
    private array $counters = [];

    public function __construct(callable $callback)
    {
        $this->callback = \Closure::fromCallable($callback);
    }

    /**
     * Set reset every N numbers.
     */
    public function resetEvery(int $count): self
    {
        $this->resetEvery = $count;
        return $this;
    }

    /**
     * Set numbers to skip.
     */
    public function skip(array $numbers): self
    {
        $this->skipNumbers = $numbers;
        return $this;
    }

    /**
     * Set maximum limit.
     */
    public function maxLimit(int $limit, string $onReach = 'throw'): self
    {
        $this->maxLimit = $limit;
        $this->onMaxReach = $onReach;
        return $this;
    }

    /**
     * Get next counter with all features applied.
     */
    public function next(string $key, array $context = []): int
    {
        // Initialize counter for this key if not exists
        if (!isset($this->counters[$key])) {
            $this->counters[$key] = (int) ($this->callback)($key, $context);
        } else {
            // Increment counter
            $this->counters[$key]++;
        }

        $counter = $this->counters[$key];

        // Skip numbers
        while (in_array($counter, $this->skipNumbers, true)) {
            $counter++;
            
            // Check limit after skip
            if ($this->maxLimit !== null && $counter > $this->maxLimit) {
                if ($this->onMaxReach === 'throw') {
                    throw new \RuntimeException(
                        "Maximum limit {$this->maxLimit} reached after skipping"
                    );
                }
                // Reset to 1
                $counter = 1;
            }
            
            // Re-apply resetEvery after skip
            if ($this->resetEvery !== null) {
                $counter = (($counter - 1) % $this->resetEvery) + 1;
            }
        }

        // Apply resetEvery
        if ($this->resetEvery !== null) {
            $counter = (($counter - 1) % $this->resetEvery) + 1;
        }

        // Check max limit
        if ($this->maxLimit !== null && $counter > $this->maxLimit) {
            if ($this->onMaxReach === 'throw') {
                throw new \RuntimeException(
                    "Maximum limit of {$this->maxLimit} reached"
                );
            }
            // Reset to 1
            $counter = 1;
            $this->counters[$key] = 1;
        } else {
            // Update internal counter
            $this->counters[$key] = $counter;
        }

        return $counter;
    }

    /**
     * Calculate next valid number considering skips.
     */
    public function calculateNext(int $current): int
    {
        $next = $current + 1;
        
        while (in_array($next, $this->skipNumbers, true)) {
            $next++;
        }
        
        return $next;
    }
}
