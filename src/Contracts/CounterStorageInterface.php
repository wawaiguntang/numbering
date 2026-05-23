<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering\Contracts;

/**
 * Interface for counter storage implementations.
 * Library users implement this to integrate with their database.
 */
interface CounterStorageInterface
{
    /**
     * Get the next counter value.
     *
     * @param string $key Unique identifier for this counter
     * @param array<string, mixed> $context Additional context (date, period, etc.)
     * @return int The next counter value
     */
    public function next(string $key, array $context = []): int;
}
