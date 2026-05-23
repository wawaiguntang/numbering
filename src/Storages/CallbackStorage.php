<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering\Storages;

use Wawaiguntang\Numbering\Contracts\CounterStorageInterface;

/**
 * Wrapper for callable counter implementation.
 * Useful for database integration.
 */
class CallbackStorage implements CounterStorageInterface
{
    private \Closure $callback;

    public function __construct(callable $callback)
    {
        $this->callback = \Closure::fromCallable($callback);
    }

    public function next(string $key, array $context = []): int
    {
        return (int) ($this->callback)($key, $context);
    }
}
