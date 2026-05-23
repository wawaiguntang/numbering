# Examples

This folder contains usage examples for different frameworks and use cases.

## Structure

```
examples/
├── laravel/           # Laravel framework integration
├── codeigniter/       # CodeIgniter 3/4 integration
├── plain-php/         # Plain PHP (no framework)
└── use-cases/
    └── simrs/         # SIMRS (Hospital Information System) use cases
```

## Quick Navigation

- [Laravel Integration](./laravel/) - Service provider, facade, Eloquent examples
- [CodeIgniter Integration](./codeigniter/) - Library wrapper, helper functions
- [Plain PHP](./plain-php/) - PDO, manual setup
- [SIMRS Use Cases](./use-cases/simrs/) - Real-world hospital numbering (Poli, IGD, RI, Lab)

## Common Patterns

### Basic Usage
```php
use Wawaiguntang\Numbering\Numbering;

$number = (new Numbering())
    ->pattern('INV/{Y}/{sequence:4}')
    ->setCounter(fn() => getNextNumber())
    ->generate();
```

### With Roman Numerals
```php
$number = (new Numbering())
    ->pattern('RI/{romanDate}/{roman}')
    ->setCounter(fn() => getNextNumber())
    ->generate(); // RI/XXIII/V/MMXXV/I
```

### From Database Counter
```php
$number = (new Numbering())
    ->prefix('PU')
    ->date('Ym')
    ->sequence(3)
    ->setCounter(function() {
        // Your DB logic here
        DB::table('counters')->where('key', 'poli')->increment('value');
        return DB::table('counters')->where('key', 'poli')->value('value');
    })
    ->generate();
```
