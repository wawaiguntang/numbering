# SIMRS (Sistem Informasi Rumah Sakit) Use Cases

Real-world numbering implementations for Hospital Information Systems.

## Overview

| Module | Pattern | Example |
|--------|---------|---------|
| **Poliklinik** | `{prefix}{kodePoli}{Ym}{sequence:3}` | PUA202505001 |
| **IGD** | `IGD/{romanDate}/{sequence:2}` | IGD/23/V/MMXXV/01 |
| **Rawat Inap** | `RI/{kodeBangsal}/{roman}` | RI/MEL/I |
| **Resep** | `RSP/{noRM}/{Ymd}/{sequence:2}` | RSP/123456/20250523/01 |
| **Laboratorium** | `LAB/{romanDateShort}/{sequence:3}` | LAB/23/V/XXV/001 |
| **Radiologi** | `RAD/{Y}{sequence:4}` | RAD20250001 |
| **Kamar Operasi** | `OK/{romanMonth}/{Y}/{sequence:2}` | OK/V/2025/01 |

## Installation

```bash
composer require wawaiguntang/numbering
```

## Configuration

Create `config/poliklinik.php`:

```php
<?php

return [
    'umum' => [
        'pattern' => '{prefix}{kodePoli}{Ym}{sequence:3}',
        'params' => [
            'prefix' => 'PU',
            'kodePoli' => 'A',
        ],
        'sequence_length' => 3,
        'reset' => 'monthly',
    ],
    'gigi' => [
        'pattern' => '{prefix}{kodePoli}{Ym}{sequence:3}',
        'params' => [
            'prefix' => 'PU',
            'kodePoli' => 'G',
        ],
        'reset' => 'monthly',
    ],
    'anak' => [
        'pattern' => '{prefix}{kodePoli}{Ym}{sequence:3}',
        'params' => [
            'prefix' => 'PU',
            'kodePoli' => 'AN',
        ],
        'reset' => 'monthly',
    ],
];
```

Create `config/igd.php`:

```php
<?php

return [
    'pattern' => 'IGD/{romanDate}/{sequence:2}',
    'sequence_length' => 2,
    'reset' => 'daily',
];
```

Create `config/rawat_inap.php`:

```php
<?php

return [
    'melati' => [
        'pattern' => 'RI/{kodeBangsal}/{roman}',
        'params' => ['kodeBangsal' => 'MEL'],
        'roman_sequence' => true,
        'reset' => 'yearly',
    ],
    'mawar' => [
        'pattern' => 'RI/{kodeBangsal}/{roman}',
        'params' => ['kodeBangsal' => 'MAW'],
        'roman_sequence' => true,
        'reset' => 'yearly',
    ],
    'dahlia' => [
        'pattern' => 'RI/{kodeBangsal}/{sequence:3}',
        'params' => ['kodeBangsal' => 'DAH'],
        'reset' => 'yearly',
    ],
];
```

## Generator Classes

Create `NoRegistrasiGenerator.php`:

```php
<?php

namespace App\SIMRS;

use Wawaiguntang\Numbering\Numbering;

class NoRegistrasiGenerator
{
    private $counterService;
    
    public function __construct($counterService)
    {
        $this->counterService = $counterService;
    }
    
    /**
     * Generate nomor registrasi poliklinik
     */
    public function forPoliklinik(string $kodePoli): string
    {
        $config = require __DIR__ . '/config/poliklinik.php';
        $poli = $config[$kodePoli] ?? $config['umum'];
        
        $numbering = new Numbering($poli['pattern']);
        
        foreach ($poli['params'] as $key => $value) {
            $numbering->param($key, $value);
        }
        
        return $numbering
            ->sequence($poli['sequence_length'] ?? 3)
            ->reset($poli['reset'] ?? 'monthly')
            ->setCounter(fn() => $this->counterService->next('poli_' . $kodePoli, $poli['reset']))
            ->generate();
    }
    
    /**
     * Generate nomor registrasi IGD
     */
    public function forIGD(): string
    {
        $config = require __DIR__ . '/config/igd.php';
        
        return (new Numbering($config['pattern']))
            ->sequence($config['sequence_length'] ?? 2)
            ->reset($config['reset'] ?? 'daily')
            ->setCounter(fn() => $this->counterService->next('igd', 'daily'))
            ->generate();
    }
    
    /**
     * Generate nomor registrasi Rawat Inap
     */
    public function forRawatInap(string $kodeBangsal): string
    {
        $config = require __DIR__ . '/config/rawat_inap.php';
        $bangsal = $config[$kodeBangsal] ?? $config['melati'];
        
        $numbering = new Numbering($bangsal['pattern']);
        
        foreach ($bangsal['params'] as $key => $value) {
            $numbering->param($key, $value);
        }
        
        if ($bangsal['roman_sequence'] ?? false) {
            $numbering->romanSequence();
        } else {
            $numbering->sequence($bangsal['sequence_length'] ?? 3);
        }
        
        return $numbering
            ->reset($bangsal['reset'] ?? 'yearly')
            ->setCounter(fn() => $this->counterService->next('ri_' . $kodeBangsal, $bangsal['reset']))
            ->generate();
    }
}
```

Create `NoResepGenerator.php`:

```php
<?php

namespace App\SIMRS;

use Wawaiguntang\Numbering\Numbering;

class NoResepGenerator
{
    private $counterService;
    
    public function __construct($counterService)
    {
        $this->counterService = $counterService;
    }
    
    /**
     * Generate nomor resep
     */
    public function generate(string $noRM): string
    {
        return (new Numbering())
            ->pattern('RSP/{noRM}/{Ymd}/{sequence:2}')
            ->param('noRM', $noRM)
            ->sequence(2)
            ->reset('daily')
            ->setCounter(fn() => $this->counterService->next('resep_' . date('Ymd'), 'daily'))
            ->generate();
    }
    
    /**
     * Generate nomor resep dengan roman date
     */
    public function generateWithRoman(string $noRM): string
    {
        return (new Numbering())
            ->pattern('RSP/{noRM}/{romanDateShort}/{sequence:2}')
            ->param('noRM', $noRM)
            ->sequence(2)
            ->reset('daily')
            ->setCounter(fn() => $this->counterService->next('resep_roman_' . date('Ymd'), 'daily'))
            ->generate();
    }
}
```

## Usage Examples

```php
<?php

use App\SIMRS\NoRegistrasiGenerator;
use App\SIMRS\NoResepGenerator;

// Initialize with your counter service
$counterService = new YourCounterService(); // DB implementation
$regGenerator = new NoRegistrasiGenerator($counterService);
$resepGenerator = new NoResepGenerator($counterService);

// Poliklinik
echo $regGenerator->forPoliklinik('umum');  // PUA202505001
echo $regGenerator->forPoliklinik('gigi');  // PUG202505001
echo $regGenerator->forPoliklinik('anak');  // PUAN202505001

// IGD
echo $regGenerator->forIGD();  // IGD/23/V/MMXXV/01

// Rawat Inap dengan Roman
echo $regGenerator->forRawatInap('melati'); // RI/MEL/I
echo $regGenerator->forRawatInap('melati'); // RI/MEL/II
echo $regGenerator->forRawatInap('mawar');  // RI/MAW/I

// Resep
echo $resepGenerator->generate('123456');        // RSP/123456/20250523/01
echo $resepGenerator->generateWithRoman('789'); // RSP/789/23/V/XXV/01
```

## Laboratorium & Radiologi

```php
<?php

class NoLabGenerator
{
    private $counterService;
    
    public function generatePemeriksaan(): string
    {
        return (new Numbering())
            ->pattern('LAB/{romanDateShort}/{sequence:3}')
            ->sequence(3)
            ->reset('daily')
            ->setCounter(fn() => $this->counterService->next('lab', 'daily'))
            ->generate(); // LAB/23/V/XXV/001
    }
}

class NoRadiologiGenerator
{
    private $counterService;
    
    public function generatePemeriksaan(): string
    {
        return (new Numbering())
            ->pattern('RAD/{Y}{sequence:4}')
            ->sequence(4)
            ->reset('yearly')
            ->setCounter(fn() => $this->counterService->next('rad', 'yearly'))
            ->generate(); // RAD20250001
    }
}

class NoOKGenerator
{
    private $counterService;
    
    public function generateJadwal(): string
    {
        return (new Numbering())
            ->pattern('OK/{romanMonth}/{Y}/{sequence:2}')
            ->sequence(2)
            ->reset('monthly')
            ->setCounter(fn() => $this->counterService->next('ok', 'monthly'))
            ->generate(); // OK/V/2025/01
    }
}
```

## Advanced: Custom Pattern per Unit

```php
<?php

class CustomNumbering
{
    private $db;
    
    public function generate(string $unit, ?string $customPattern = null): string
    {
        // Get unit config from database
        $config = $this->db->query("SELECT * FROM numbering_configs WHERE unit = ?", [$unit])->fetch();
        
        $pattern = $customPattern ?? $config['pattern'];
        
        return (new Numbering($pattern))
            ->setCounter(fn() => $this->getNextFromDB($unit, $config['reset_period']))
            ->generate();
    }
}

// Usage
$custom = new CustomNumbering($db);
echo $custom->generate('poli_umum');
echo $custom->generate('poli_gigi', 'PG/{romanMonth}/{sequence:3}');
```
