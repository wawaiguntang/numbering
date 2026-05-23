<?php

declare(strict_types=1);

namespace Wawaiguntang\Numbering;

use Wawaiguntang\Numbering\Contracts\CounterStorageInterface;

/**
 * Main numbering generator with fluent API.
 */
class Numbering
{
    private ?string $pattern = null;
    private Formatter $formatter;
    private ?CounterStorageInterface $storage = null;
    private ?\Closure $counterCallback = null;
    
    private array $params = [];
    private ?int $sequenceLength = null;
    private bool $romanSequence = false;
    private ?string $resetPeriod = null;
    private ?string $customResetDate = null;
    private ?\DateTimeInterface $date = null;
    private ?\Closure $transformCallback = null;
    private bool $uppercase = false;
    private bool $lowercase = false;
    
    private static array $templates = [];

    public function __construct(?string $pattern = null)
    {
        $this->pattern = $pattern;
        $this->formatter = new Formatter();
    }

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
        $this->formatter->setParam($name, $value);
        return $this;
    }

    /**
     * Set sequence configuration.
     */
    public function sequence(int $length, ?string $padChar = '0'): self
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
     * Set date (default: current).
     */
    public function date(string $format, ?string $value = null): self
    {
        if ($value !== null) {
            $this->date = \DateTimeImmutable::createFromFormat($format, $value) ?: new \DateTimeImmutable();
        }
        return $this;
    }

    /**
     * Set Roman date shorthand.
     */
    public function romanDate(): self
    {
        // Will be handled by pattern or default format
        return $this;
    }

    /**
     * Set reset period for counter.
     */
    public function reset(string $period, ?string $customDate = null): self
    {
        $this->resetPeriod = $period;
        $this->customResetDate = $customDate;
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
     * Set storage implementation.
     */
    public function setStorage(CounterStorageInterface $storage): self
    {
        $this->storage = $storage;
        return $this;
    }

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

    /**
     * Generate the final number.
     */
    public function generate(): string
    {
        $pattern = $this->buildPattern();
        $date = $this->date ?? new \DateTimeImmutable();
        
        // Get counter value
        $counter = $this->getCounter($date);
        
        // Set up formatter
        foreach ($this->params as $name => $value) {
            $this->formatter->setParam($name, $value);
        }
        
        if ($counter !== null) {
            $this->formatter->setSequence($counter, $this->sequenceLength);
        }
        
        if ($this->romanSequence) {
            $this->formatter->useRomanSequence(true);
        }
        
        // Format
        $result = $this->formatter->format($pattern, $date);
        
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
     * Generate as array with parts.
     */
    public function generateArray(): array
    {
        return [
            'number' => $this->generate(),
            'pattern' => $this->buildPattern(),
            'params' => $this->params,
            'date' => ($this->date ?? new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Save template.
     */
    public static function saveTemplate(string $name, array $config): void
    {
        self::$templates[$name] = $config;
    }

    /**
     * Load from template.
     */
    public static function fromTemplate(string $name): self
    {
        $config = self::$templates[$name] ?? throw new \InvalidArgumentException("Template '{$name}' not found");
        
        $instance = new self($config['pattern'] ?? null);
        
        if (isset($config['params'])) {
            foreach ($config['params'] as $key => $value) {
                $instance->param($key, $value);
            }
        }
        
        if (isset($config['sequence'])) {
            $instance->sequence($config['sequence']);
        }
        
        if (isset($config['roman']) && $config['roman']) {
            $instance->romanSequence();
        }
        
        return $instance;
    }

    /**
     * Build pattern from components if not explicitly set.
     */
    private function buildPattern(): string
    {
        if ($this->pattern !== null) {
            return $this->pattern;
        }
        
        // Build from components
        $parts = [];
        
        if (isset($this->params['prefix'])) {
            $parts[] = '{prefix}';
        }
        
        // Default pattern with sequence
        $parts[] = $this->romanSequence ? '{roman}' : '{sequence}';
        
        if (isset($this->params['suffix'])) {
            $parts[] = '{suffix}';
        }
        
        return implode('', $parts);
    }

    /**
     * Get counter value from storage or callback.
     */
    private function getCounter(\DateTimeInterface $date): ?int
    {
        $key = $this->buildCounterKey($date);
        $context = [
            'date' => $date,
            'period' => $this->resetPeriod,
            'params' => $this->params,
        ];
        
        if ($this->storage !== null) {
            return $this->storage->next($key, $context);
        }
        
        if ($this->counterCallback !== null) {
            return ($this->counterCallback)($key, $context);
        }
        
        return null;
    }

    /**
     * Build counter key based on reset period.
     */
    private function buildCounterKey(\DateTimeInterface $date): string
    {
        $base = 'default';
        
        if (isset($this->params['prefix'])) {
            $base = $this->params['prefix'];
        }
        
        return match ($this->resetPeriod) {
            'daily' => $base . '_' . $date->format('Ymd'),
            'monthly' => $base . '_' . $date->format('Ym'),
            'yearly' => $base . '_' . $date->format('Y'),
            default => $base,
        };
    }
}
