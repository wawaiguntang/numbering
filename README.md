# Numbering

Dynamic numbering library with pattern-based formatting, Roman numerals, and database counter support.

```php
$number = (new Numbering())
    ->pattern('PU/{romanMonth}/{year}/{sequence:3}')
    ->setCounter(fn() => getNextFromDB())
    ->generate(); // PU/V/2025/001
```

## Features

- **Pattern-based** - Flexible string patterns with placeholders
- **Roman Numerals** - Full support for Roman date and sequence (I, II, III, MMXXV)
- **Database Counter** - Callback-based counter (no DB dependency in library)
- **Auto Reset** - Daily, monthly, yearly counter reset
- **Framework Agnostic** - Works with Laravel, CodeIgniter, or plain PHP
- **Zero Dependencies** - Pure PHP 8.1+

## Installation

```bash
composer require wawaiguntang/numbering
```

## Quick Start

```php
use Wawaiguntang\Numbering\Numbering;

// Basic with pattern
$number = (new Numbering())
    ->pattern('INV/{Y}/{sequence:4}')
    ->setCounter(fn() => 1)
    ->generate();

// With database counter
$number = (new Numbering())
    ->prefix('PU')
    ->date('Ym')
    ->sequence(3)
    ->setCounter(function() {
        // Your DB logic
        return DB::table('counters')->increment('value');
    })
    ->generate(); // PUA202505001
```

## Supported Patterns

| Placeholder | Output | Description |
|-------------|--------|-------------|
| `{prefix}`, `{prefix:XXX}` | `PU`, `IGD` | Static prefix |
| `{suffix}`, `{suffix:XXX}` | `A`, `UMUM` | Static suffix |
| `{sequence}`, `{sequence:N}` | `001`, `0001` | Numeric sequence with padding |
| `{roman}`, `{roman:N}` | `I`, `II`, `III` | Roman numeral sequence |
| `{date}`, `{date:FORMAT}` | `20250523` | PHP date format |
| `{year}`, `{month}`, `{day}` | `2025`, `05`, `23` | Date parts |
| `{romanMonth}` | `I` - `XII` | Roman month (I-XII) |
| `{romanYear}` | `MMXXV` | Full year in Roman |
| `{romanYearShort}` | `XXV` | Short year (YY) in Roman |
| `{romanDate}` | `XXIII/V/MMXXV` | Full date: dd/M/yyyy |
| `{romanDateShort}` | `23/V/25` | Date: dd/M/yy |
| `{param:NAME}` | `A`, `MEL` | Custom parameters |
| `{random:N}` | `A3B9` | Random alphanumeric |
| Literal `/`, `-`, etc. | `/`, `-` | Any separator |

## Examples

### Counter from Database

```php
$numbering = new Numbering();

// Counter numeric
$number = $numbering
    ->pattern('PU/{romanMonth}/{year}/{sequence:3}')
    ->setCounter(function() {
        DB::table('number_counters')
            ->where('key', 'poli_umum')
            ->increment('last');
        return DB::table('number_counters')
            ->where('key', 'poli_umum')
            ->value('last');
    })
    ->generate(); // PU/V/2025/001, PU/V/2025/002...

// Counter with Roman
$number = $numbering
    ->pattern('RI/{romanDate}/{roman}')
    ->setCounter(fn() => getNextCounter())
    ->generate(); // RI/XXIII/V/MMXXV/I, RI/XXIII/V/MMXXV/II...
```

### SIMRS (Hospital) Examples

```php
// Poliklinik Umum
$number = (new Numbering())
    ->pattern('{prefix}{kodePoli}{Ym}{sequence:3}')
    ->param('prefix', 'PU')
    ->param('kodePoli', 'A')
    ->setCounter(fn() => getNext('poli_umum'))
    ->generate(); // PUA202505001

// IGD with Roman Date
$number = (new Numbering())
    ->pattern('IGD/{romanDate}/{sequence:2}')
    ->setCounter(fn() => getNext('igd'))
    ->generate(); // IGD/23/V/MMXXV/01

// Rawat Inap with Roman Counter
$number = (new Numbering())
    ->pattern('RI/{kodeBangsal}/{roman}')
    ->param('kodeBangsal', 'MEL')
    ->setCounter(fn() => getNext('ri_melati'))
    ->romanSequence()
    ->generate(); // RI/MEL/I, RI/MEL/II...

// Resep
$number = (new Numbering())
    ->pattern('RSP/{noRM}/{Ymd}/{sequence:2}')
    ->param('noRM', '123456')
    ->setCounter(fn() => getNext('resep'))
    ->generate(); // RSP/123456/20250523/01
```

### Reset Period

```php
$numbering = (new Numbering())
    ->pattern('REG/{Ym}/{sequence:3}')
    ->reset('monthly')  // Counter resets monthly
    ->setCounter(fn() => getNext())
    ->generate();

// Available: never, daily, monthly, yearly
```

### Transformations

```php
$number = (new Numbering())
    ->pattern('test123')
    ->uppercase()
    ->generate(); // TEST123

$number = (new Numbering())
    ->pattern('TEST123')
    ->lowercase()
    ->generate(); // test123

$number = (new Numbering())
    ->pattern('TEST123')
    ->transform(fn($s) => str_replace('0', 'O', $s))
    ->generate();
```

## Framework Integration

- **[Laravel](./examples/laravel/)** - Service provider, facade, migration
- **[CodeIgniter](./examples/codeigniter/)** - Library wrapper, helper functions
- **[Plain PHP](./examples/plain-php/)** - PDO, manual setup
- **[SIMRS Use Cases](./examples/use-cases/simrs/)** - Complete hospital examples

## API Reference

### Constructor

```php
$numbering = new Numbering(?string $pattern = null);
```

### Methods

| Method | Description |
|--------|-------------|
| `pattern(string $pattern)` | Set pattern string |
| `prefix(string $value)` | Set prefix value |
| `suffix(string $value)` | Set suffix value |
| `param(string $name, string $value)` | Set custom parameter |
| `sequence(int $length)` | Set sequence padding length |
| `romanSequence(bool $enable)` | Use Roman numerals for sequence |
| `date(string $format, ?string $value)` | Set date or custom date |
| `reset(string $period)` | Set reset period (daily/monthly/yearly) |
| `setCounter(callable $callback)` | Set counter callback |
| `setStorage(CounterStorageInterface $storage)` | Set storage implementation |
| `uppercase()` / `lowercase()` | Transform result case |
| `transform(callable $callback)` | Custom transformation |
| `generate(): string` | Generate final number |
| `generateArray(): array` | Generate with metadata |

### Static Methods

```php
// Save template
Numbering::saveTemplate('poli_umum', [
    'pattern' => '{prefix}{kodePoli}{Ym}{sequence:3}',
    'params' => ['prefix' => 'PU', 'kodePoli' => 'A'],
]);

// Load from template
$numbering = Numbering::fromTemplate('poli_umum');
```

## Storage Implementations

### Memory Storage (Testing)

```php
use Wawaiguntang\Numbering\Storages\MemoryStorage;

$storage = new MemoryStorage();
$numbering->setStorage($storage);
```

### Callback Storage

```php
use Wawaiguntang\Numbering\Storages\CallbackStorage;

$storage = new CallbackStorage(fn($key) => getFromDB($key));
$numbering->setStorage($storage);
```

### Custom Storage

```php
use Wawaiguntang\Numbering\Contracts\CounterStorageInterface;

class MyStorage implements CounterStorageInterface
{
    public function next(string $key, array $context = []): int
    {
        // Your implementation
        return getNextNumber($key);
    }
}

$numbering->setStorage(new MyStorage());
```

## Testing

```bash
composer install
vendor/bin/phpunit
```

## License

MIT License - see [LICENSE](./LICENSE) file.

## Contributing

Pull requests welcome! Please include tests.

---

**Made with ❤️ for SIMRS and healthcare systems.**
