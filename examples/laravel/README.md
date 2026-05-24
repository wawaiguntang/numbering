# Laravel Integration

Example implementation for Laravel applications.

## Installation

```bash
composer require wawaiguntang/numbering
```

## Service Provider

Create `app/Providers/NumberingServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Wawaiguntang\Numbering\Numbering;

class NumberingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Numbering::class, function () {
            return new Numbering();
        });
    }
}
```

Register in `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\NumberingServiceProvider::class,
],
```

## Migration

Create counter table:

```bash
php artisan make:migration create_numbering_counters_table
```

```php
Schema::create('numbering_counters', function (Blueprint $table) {
    $table->id();
    $table->string('counter_key')->unique();
    $table->unsignedInteger('last_number')->default(0);
    $table->string('period')->nullable(); // YYYY-MM for monthly reset
    $table->timestamps();
});
```

## Counter Service

Create `app/Services/CounterService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CounterService
{
    public function next(string $key, string $resetPeriod = 'monthly'): int
    {
        $period = match ($resetPeriod) {
            'monthly' => now()->format('Y-m'),
            'yearly' => now()->format('Y'),
            'daily' => now()->format('Y-m-d'),
            default => null,
        };
        
        return DB::transaction(function () use ($key, $period) {
            $record = DB::table('numbering_counters')
                ->where('counter_key', $key)
                ->where('period', $period)
                ->lockForUpdate()
                ->first();
            
            if (!$record) {
                DB::table('numbering_counters')->insert([
                    'counter_key' => $key,
                    'last_number' => 1,
                    'period' => $period,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                return 1;
            }
            
            DB::table('numbering_counters')
                ->where('id', $record->id)
                ->update([
                    'last_number' => $record->last_number + 1,
                    'updated_at' => now(),
                ]);
            
            return $record->last_number + 1;
        });
    }
}
```

## Controller Usage

```php
<?php

namespace App\Http\Controllers;

use App\Services\CounterService;
use Wawaiguntang\Numbering\Numbering;

class PendaftaranController extends Controller
{
    public function __construct(
        private CounterService $counterService
    ) {}

    public function store(Request $request)
    {
        // Poliklinik Umum
        $noRegistrasi = (new Numbering())
            ->pattern('{prefix}{kodePoli}{Ym}{sequence:3}')
            ->param('prefix', 'PU')
            ->param('kodePoli', 'A')
            ->setCounter(fn() => $this->counterService->next('poli_umum', 'monthly'))
            ->generate();
        
        // IGD
        $noRegistrasiIGD = (new Numbering())
            ->pattern('IGD/{romanDate}/{sequence:2}')
            ->setCounter(fn() => $this->counterService->next('igd', 'daily'))
            ->generate();
        
        // Rawat Inap dengan Roman
        $noRegistrasiRI = (new Numbering())
            ->pattern('RI/{kodeBangsal}/{roman}')
            ->param('kodeBangsal', 'MEL')
            ->setCounter(fn() => $this->counterService->next('ri_melati', 'yearly'))
            ->romanSequence()
            ->generate();
        
        Pendaftaran::create([
            'no_registrasi' => $noRegistrasi,
            // ... other fields
        ]);
    }
}
```

## Advanced Numbering dengan Database

Untuk fitur advanced (reset tiap N nomor, skip nomor, limit maksimum):

### Advanced Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Wawaiguntang\Numbering\AdvancedNumbering;
use Wawaiguntang\Numbering\Storages\AdvancedStorage;

class AdvancedNumberingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('advanced.numbering', function () {
            // Buat storage dengan DB transaction dan locking
            $storage = new AdvancedStorage(function($key, $context) {
                return DB::transaction(function() use ($key, $context) {
                    $record = DB::table('numbering_counters')
                        ->where('counter_key', $key)
                        ->lockForUpdate()
                        ->first();
                    
                    if (!$record) {
                        DB::table('numbering_counters')->insert([
                            'counter_key' => $key,
                            'last_number' => 1,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        return 1;
                    }
                    
                    DB::table('numbering_counters')
                        ->where('id', $record->id)
                        ->update([
                            'last_number' => $record->last_number + 1,
                            'updated_at' => now(),
                        ]);
                    
                    return $record->last_number + 1;
                });
            });
            
            return $storage;
        });
    }
}
```

### Advanced Controller Usage

```php
<?php

namespace App\Http\Controllers;

use Wawaiguntang\Numbering\AdvancedNumbering;

class PendaftaranAdvancedController extends Controller
{
    public function storePoliklinik(Request $request)
    {
        $storage = app('advanced.numbering');
        
        $noRegistrasi = (new AdvancedNumbering())
            ->pattern('PU/{kodePoli}/{sequence:3}')
            ->param('kodePoli', $request->kode_poli)
            ->setAdvancedStorage($storage)
            ->resetEvery(999)              // Reset tiap 999 nomor
            ->skip([4, 13, 44])           // Skip nomor "tidak bagus"
            ->maxLimit(999, 'reset')       // Auto-reset jika mencapai 999
            ->padChar('0')                // Padding dengan 0
            ->generate();
        
        Pendaftaran::create([
            'no_registrasi' => $noRegistrasi,
            // ...
        ]);
    }
    
    public function storeIGD(Request $request)
    {
        $storage = app('advanced.numbering');
        
        $noRegistrasi = (new AdvancedNumbering())
            ->pattern('IGD/{romanDate}/{sequence:2}')
            ->setAdvancedStorage($storage)
            ->resetEvery(50)              // Reset tiap 50 per hari
            ->skip([4, 13])              // Skip nomor tertentu
            ->generate();
        
        IGD::create([
            'no_registrasi' => $noRegistrasi,
            // ...
        ]);
    }
}
```

## Facade (Optional)

Create `app/Facades/Numbering.php`:

```php
<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class Numbering extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Wawaiguntang\Numbering\Numbering::class;
    }
}
```

Usage:

```php
use App\Facades\Numbering;

$number = Numbering::pattern('INV/{Y}/{sequence:4}')->generate();
```
