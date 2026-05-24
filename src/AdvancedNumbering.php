<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering;

use Wawaiguntang\Numbering\Contracts\CounterStorageInterface;
use Wawaiguntang\Numbering\Storages\AdvancedStorage;

/**
 * Advanced numbering generator dengan fitur lengkap untuk SIMRS.
 * 
 * Features:
 * - resetEvery(int $count): Reset counter setiap N nomor
 * - resetWhen(callable $condition): Kondisi custom untuk reset
 * - maxLimit(int $limit, string $onReach): Limit maksimum
 * - skip(array $numbers): Skip nomor tertentu (nomor yang tidak dipakai)
 * - padChar(string $char): Custom padding character
 * 
 * @example
 * use Wawaiguntang\Numbering\AdvancedNumbering;
 * use Wawaiguntang\Numbering\Storages\AdvancedStorage;
 * 
 * $storage = new AdvancedStorage(fn() => getNextFromDB());
 * $numbering = new AdvancedNumbering('PU/{sequence:3}');
 * $numbering
 *     ->setAdvancedStorage($storage)
 *     ->resetEvery(1000)           // Reset tiap 1000
 *     ->skip([4, 13, 666])         // Skip nomor tertentu
 *     ->maxLimit(999, 'reset')      // Max 999, reset jika melebihi
 *     ->padChar('0')               // Padding dengan '0'
 *     ->generate();                 // PU001, PU002, PU003, PU005 (skip 4)
 */
class AdvancedNumbering
{
    // Pattern & formatting
    private ?string $pattern = null;
    private Formatter $formatter;
    
    // Storage
    private ?AdvancedStorage $advancedStorage = null;
    private ?CounterStorageInterface $storage = null;
    private ?\Closure $counterCallback = null;
    
    // Configuration
    private array $params = [];
    private ?int $sequenceLength = null;
    private string $padChar = '0';
    private bool $romanSequence = false;
    
    // Date
    private ?\DateTimeInterface $date = null;
    
    // Transformations
    private ?\Closure $transformCallback = null;
    private bool $uppercase = false;
    private bool $lowercase = false;

    public function __construct(?string $pattern = null)
    {
        $this->pattern = $pattern;
        $this->formatter = new Formatter();
    }

    // ============ Pattern & Components ============

    /**
     * Set pattern string.
     */
    public function pattern(string $pattern): self
    {
        $this->pattern = $pattern;
        return $this;
    }

    /**
     * Set prefix value.
     */
    public function prefix(string $value): self
    {
        $this->params['prefix'] = $value;
        return $this;
    }

    /**
     * Set suffix value.
     */
    public function suffix(string $value): self
    {
        $this->params['suffix'] = $value;
        return $this;
    }

    /**
     * Set custom parameter.
     */
    public function param(string $name, string $value): self
    {
        $this->params[$name] = $value;
        return $this;
    }

    /**
     * Set sequence configuration.
     */
    public function sequence(int $length): self
    {
        $this->sequenceLength = $length;
        return $this;
    }

    /**
     * Use Roman numerals for sequence.
     */
    public function romanSequence(bool $enable = true): self
    {
        $this->romanSequence = $enable;
        return $this;
    }

    /**
     * Set padding character for sequence.
     * Default is '0' (e.g., 001, 002).
     * 
     * @param string $char Character untuk padding
     */
    public function padChar(string $char): self
    {
        $this->padChar = $char;
        return $this;
    }

    /**
     * Set date (default: current).
     */
    public function date(string $format, ?string $value = null): self
    {
        if ($value !== null) {
            $this->date = \DateTimeImmutable::createFromFormat($format, $value) ?: new \DateTimeImmutable();
        }
        return $this;
    }

    // ============ Advanced Features ============

    /**
     * Set AdvancedStorage dengan fitur lengkap.
     */
    public function setAdvancedStorage(AdvancedStorage $storage): self
    {
        $this->advancedStorage = $storage;
        $this->storage = $storage; // Compatibility
        return $this;
    }

    /**
     * Set basic storage (tanpa fitur advanced).
     */
    public function setStorage(CounterStorageInterface $storage): self
    {
        $this->storage = $storage;
        return $this;
    }

    /**
     * Set counter callback.
     */
    public function setCounter(callable $callback): self
    {
        $this->counterCallback = \Closure::fromCallable($callback);
        return $this;
    }

    /**
     * Reset counter setiap N nomor.
     * 
     * Example: resetEvery(1000) -> 1, 2, ..., 999, 1000, 1, 2...
     */
    public function resetEvery(int $count): self
    {
        if ($this->advancedStorage === null) {
            throw new \RuntimeException(
                'AdvancedStorage harus diset sebelum menggunakan resetEvery(). ' .
                'Gunakan setAdvancedStorage(new AdvancedStorage($callback))'
            );
        }
        $this->advancedStorage->resetEvery($count);
        return $this;
    }

    /**
     * Skip nomor tertentu.
     * Nomor ini tidak akan pernah digenerate.
     * Berguna untuk nomor yang sudah dipakai atau nomor yang dianggap "tidak bagus".
     * 
     * @param array $numbers Array nomor yang akan diskip
     * @example skip([4, 13, 666]) -> 1, 2, 3, 5, 6... (skip 4 dan 13)
     */
    public function skip(array $numbers): self
    {
        if ($this->advancedStorage === null) {
            throw new \RuntimeException(
                'AdvancedStorage harus diset sebelum menggunakan skip(). ' .
                'Gunakan setAdvancedStorage(new AdvancedStorage($callback))'
            );
        }
        $this->advancedStorage->skip($numbers);
        return $this;
    }

    /**
     * Set limit maksimum.
     * 
     * @param int $limit Maximum number allowed
     * @param string $onReach 'throw' untuk exception, 'reset' untuk reset ke 1
     * @example maxLimit(999, 'reset') -> Reset ke 1 jika sudah 999
     */
    public function maxLimit(int $limit, string $onReach = 'throw'): self
    {
        if ($this->advancedStorage === null) {
            throw new \RuntimeException(
                'AdvancedStorage harus diset sebelum menggunakan maxLimit(). ' .
                'Gunakan setAdvancedStorage(new AdvancedStorage($callback))'
            );
        }
        $this->advancedStorage->maxLimit($limit, $onReach);
        return $this;
    }

    // ============ Transformations ============

    /**
     * Transform result to uppercase.
     */
    public function uppercase(): self
    {
        $this->uppercase = true;
        $this->lowercase = false;
        return $this;
    }

    /**
     * Transform result to lowercase.
     */
    public function lowercase(): self
    {
        $this->lowercase = true;
        $this->uppercase = false;
        return $this;
    }

    /**
     * Custom transformation callback.
     */
    public function transform(callable $callback): self
    {
        $this->transformCallback = \Closure::fromCallable($callback);
        return $this;
    }

    // ============ Generation ============

    /**
     * Generate nomor dengan fitur advanced.
     */
    public function generate(): string
    {
        $pattern = $this->pattern ?? $this->buildDefaultPattern();
        $date = $this->date ?? new \DateTimeImmutable();
        
        // Get counter
        $counter = $this->getCounterValue($date);
        
        // Setup formatter
        $formatter = new Formatter();
        
        // Set params
        foreach ($this->params as $name => $value) {
            $formatter->setParam($name, $value);
        }
        
        // Set sequence dengan padChar
        if ($counter !== null) {
            $formatter->setSequence($counter, $this->sequenceLength);
            $formatter->setPadChar($this->padChar);
        }
        
        // Set roman sequence
        if ($this->romanSequence) {
            $formatter->useRomanSequence(true);
        }
        
        // Format
        $result = $formatter->format($pattern, $date);
        
        // Apply transformations
        if ($this->transformCallback !== null) {
            $result = ($this->transformCallback)($result);
        }
        
        if ($this->uppercase) {
            $result = strtoupper($result);
        } elseif ($this->lowercase) {
            $result = strtolower($result);
        }
        
        return $result;
    }

    /**
     * Generate dengan metadata.
     */
    public function generateWithMeta(): array
    {
        return [
            'number' => $this->generate(),
            'pattern' => $this->pattern ?? $this->buildDefaultPattern(),
            'params' => $this->params,
            'padChar' => $this->padChar,
            'date' => ($this->date ?? new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    // ============ Private Methods ============

    /**
     * Get counter value dari storage.
     */
    private function getCounterValue(\DateTimeInterface $date): ?int
    {
        if ($this->advancedStorage !== null) {
            return $this->advancedStorage->next('default', [
                'date' => $date,
                'params' => $this->params,
            ]);
        }
        
        if ($this->storage !== null) {
            return $this->storage->next('default', [
                'date' => $date,
                'params' => $this->params,
            ]);
        }
        
        if ($this->counterCallback !== null) {
            return ($this->counterCallback)('default', ['date' => $date]);
        }
        
        return null;
    }

    /**
     * Build default pattern.
     */
    private function buildDefaultPattern(): string
    {
        $parts = [];
        
        if (isset($this->params['prefix'])) {
            $parts[] = '{prefix}';
        }
        
        $parts[] = $this->romanSequence ? '{roman}' : '{sequence}';
        
        if (isset($this->params['suffix'])) {
            $parts[] = '{suffix}';
        }
        
        return implode('', $parts);
    }
}
