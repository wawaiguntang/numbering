# Plain PHP Integration

Example for plain PHP without any framework.

## Installation

```bash
composer require wawaiguntang/numbering
```

Or manual download and include:

```php
require_once 'path/to/numbering/vendor/autoload.php';
```

## Basic Setup

```php
<?php

require_once 'vendor/autoload.php';

use Wawaiguntang\Numbering\Numbering;

// Simple counter (in-memory)
$counter = 0;

$numbering = new Numbering();
$number = $numbering
    ->pattern('INV/{Y}/{sequence:4}')
    ->setCounter(function() use (&$counter) {
        return ++$counter;
    })
    ->generate();

echo $number; // INV/2025/0001
```

## With PDO Database

```php
<?php

require_once 'vendor/autoload.php';

use Wawaiguntang\Numbering\Numbering;

// Database connection
$pdo = new PDO('mysql:host=localhost;dbname=simrs;charset=utf8', 'user', 'password');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Counter function
function getNextCounter(PDO $pdo, string $key, string $period = 'monthly'): int
{
    $periodValue = match ($period) {
        'monthly' => date('Y-m'),
        'yearly' => date('Y'),
        'daily' => date('Y-m-d'),
        default => null,
    };
    
    $pdo->beginTransaction();
    
    try {
        // Try to get existing
        $stmt = $pdo->prepare(
            "SELECT id, last_number FROM numbering_counters 
             WHERE counter_key = ? AND period = ? 
             FOR UPDATE"
        );
        $stmt->execute([$key, $periodValue]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) {
            // Insert new
            $stmt = $pdo->prepare(
                "INSERT INTO numbering_counters (counter_key, last_number, period) 
                 VALUES (?, 1, ?)"
            );
            $stmt->execute([$key, $periodValue]);
            $next = 1;
        } else {
            // Update existing
            $next = $record['last_number'] + 1;
            $stmt = $pdo->prepare(
                "UPDATE numbering_counters 
                 SET last_number = ? 
                 WHERE id = ?"
            );
            $stmt->execute([$next, $record['id']]);
        }
        
        $pdo->commit();
        return $next;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Usage
$numbering = new Numbering();

$noRegistrasi = $numbering
    ->pattern('{prefix}{kodePoli}{Ym}{sequence:3}')
    ->param('prefix', 'PU')
    ->param('kodePoli', 'A')
    ->setCounter(function() use ($pdo) {
        return getNextCounter($pdo, 'poli_umum', 'monthly');
    })
    ->generate();

echo $noRegistrasi; // PUA202505001
```

## Complete Example: Pendaftaran

```php
<?php

require_once 'vendor/autoload.php';

use Wawaiguntang\Numbering\Numbering;

class PendaftaranService
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function generateNoRegistrasiPoli(string $kodePoli): string
    {
        return (new Numbering())
            ->pattern('{prefix}{kodePoli}{Ym}{sequence:3}')
            ->param('prefix', 'PU')
            ->param('kodePoli', $kodePoli)
            ->setCounter(fn() => $this->getNextCounter('poli_' . $kodePoli, 'monthly'))
            ->generate();
    }
    
    public function generateNoRegistrasiIGD(): string
    {
        return (new Numbering())
            ->pattern('IGD/{romanDate}/{sequence:2}')
            ->setCounter(fn() => $this->getNextCounter('igd', 'daily'))
            ->generate();
    }
    
    public function generateNoRegistrasiRI(string $kodeBangsal): string
    {
        return (new Numbering())
            ->pattern('RI/{kodeBangsal}/{roman}')
            ->param('kodeBangsal', $kodeBangsal)
            ->setCounter(fn() => $this->getNextCounter('ri_' . $kodeBangsal, 'yearly'))
            ->romanSequence()
            ->generate();
    }
    
    public function generateNoResep(string $noRM): string
    {
        return (new Numbering())
            ->pattern('RSP/{noRM}/{Ymd}/{sequence:2}')
            ->param('noRM', $noRM)
            ->setCounter(fn() => $this->getNextCounter('resep_' . date('Ymd'), 'daily'))
            ->generate();
    }
    
    private function getNextCounter(string $key, string $period): int
    {
        $periodValue = match ($period) {
            'monthly' => date('Y-m'),
            'yearly' => date('Y'),
            'daily' => date('Y-m-d'),
            default => null,
        };
        
        $this->pdo->beginTransaction();
        
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, last_number FROM numbering_counters 
                 WHERE counter_key = ? AND (period = ? OR period IS NULL) 
                 FOR UPDATE"
            );
            $stmt->execute([$key, $periodValue]);
            $record = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$record) {
                $stmt = $this->pdo->prepare(
                    "INSERT INTO numbering_counters (counter_key, last_number, period) VALUES (?, 1, ?)"
                );
                $stmt->execute([$key, $periodValue]);
                $next = 1;
            } else {
                $next = $record['last_number'] + 1;
                $stmt = $this->pdo->prepare("UPDATE numbering_counters SET last_number = ? WHERE id = ?");
                $stmt->execute([$next, $record['id']]);
            }
            
            $this->pdo->commit();
            return $next;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}

// Usage
$pdo = new PDO('mysql:host=localhost;dbname=simrs', 'user', 'pass');
$service = new PendaftaranService($pdo);

echo $service->generateNoRegistrasiPoli('A') . "\n"; // PUA202505001
echo $service->generateNoRegistrasiIGD() . "\n";      // IGD/23/V/MMXXV/01
echo $service->generateNoRegistrasiRI('MEL') . "\n";  // RI/MEL/I
echo $service->generateNoResep('123456') . "\n";      // RSP/123456/20250523/01
```

## Advanced Numbering dengan PDO

Untuk fitur advanced (reset tiap N nomor, skip nomor, limit maksimum):

```php
<?php

require_once 'vendor/autoload.php';

use Wawaiguntang\Numbering\AdvancedNumbering;
use Wawaiguntang\Numbering\Storages\AdvancedStorage;

// Setup AdvancedStorage dengan PDO
$pdo = new PDO('mysql:host=localhost;dbname=simrs;charset=utf8', 'user', 'password');

$storage = new AdvancedStorage(function($key, $context) use ($pdo) {
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare(
            "SELECT id, last_number FROM numbering_counters 
             WHERE counter_key = ? 
             FOR UPDATE"
        );
        $stmt->execute([$key]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$record) {
            $stmt = $pdo->prepare(
                "INSERT INTO numbering_counters (counter_key, last_number) VALUES (?, 1)"
            );
            $stmt->execute([$key]);
            $next = 1;
        } else {
            $next = $record['last_number'] + 1;
            $stmt = $pdo->prepare("UPDATE numbering_counters SET last_number = ? WHERE id = ?");
            $stmt->execute([$next, $record['id']]);
        }
        
        $pdo->commit();
        return $next;
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
});

// Poliklinik dengan advanced features
$noPoli = (new AdvancedNumbering())
    ->pattern('PU/{kodePoli}/{sequence:3}')
    ->param('kodePoli', 'A')
    ->setAdvancedStorage($storage)
    ->resetEvery(999)              // Reset tiap 999 nomor
    ->skip([4, 13, 44])           // Skip nomor "tidak bagus"
    ->maxLimit(999, 'reset')       // Auto-reset jika mencapai 999
    ->padChar('0')                // Padding dengan 0
    ->generate();

echo $noPoli . "\n";  // PUA001, PUA002, PUA003, PUA005...

// IGD dengan advanced features
$noIGD = (new AdvancedNumbering())
    ->pattern('IGD/{romanDate}/{sequence:2}')
    ->setAdvancedStorage($storage)
    ->resetEvery(50)              // Reset tiap 50 per hari
    ->skip([4, 13])              // Skip nomor tertentu
    ->generate();

echo $noIGD . "\n";  // IGD/23/V/MMXXV/01
```

## Database Schema

```sql
CREATE TABLE numbering_counters (
    id INT AUTO_INCREMENT PRIMARY KEY,
    counter_key VARCHAR(50) NOT NULL,
    last_number INT UNSIGNED NOT NULL DEFAULT 0,
    period VARCHAR(10) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_counter_period (counter_key, period)
);
```
