# CodeIgniter Integration

Example implementation for CodeIgniter 3 and 4.

## CI 4 Installation

```bash
composer require wawaiguntang/numbering
```

## CI 3 Installation

Download and extract to `application/libraries/numbering/`:
```
application/
└── libraries/
    └── numbering/
        └── src/
            └── ...
```

## CI 4 Library Wrapper

Create `app/Libraries/NumberingService.php`:

```php
<?php

namespace App\Libraries;

use Wawaiguntang\Numbering\Numbering;

class NumberingService
{
    private Numbering $numbering;
    
    public function __construct()
    {
        $this->numbering = new Numbering();
    }
    
    public function forPoliklinik(string $kodePoli): string
    {
        $db = \Config\Database::connect();
        
        return $this->numbering
            ->pattern('{prefix}{kodePoli}{Ym}{sequence:3}')
            ->param('prefix', 'PU')
            ->param('kodePoli', $kodePoli)
            ->setCounter(function() use ($db, $kodePoli) {
                // Your DB logic
                $builder = $db->table('numbering_counters');
                $builder->where('counter_key', 'poli_' . $kodePoli);
                $builder->where('period', date('Y-m'));
                $record = $builder->get()->getRow();
                
                if (!$record) {
                    $builder->insert([
                        'counter_key' => 'poli_' . $kodePoli,
                        'last_number' => 1,
                        'period' => date('Y-m'),
                    ]);
                    return 1;
                }
                
                $builder->where('id', $record->id);
                $builder->update(['last_number' => $record->last_number + 1]);
                
                return $record->last_number + 1;
            })
            ->generate();
    }
    
    public function forIGD(): string
    {
        return $this->numbering
            ->pattern('IGD/{romanDate}/{sequence:2}')
            ->setCounter(function() {
                // Counter logic
                return $this->getNextCounter('igd', 'daily');
            })
            ->generate();
    }
    
    public function forRawatInap(string $kodeBangsal): string
    {
        return $this->numbering
            ->pattern('RI/{kodeBangsal}/{roman}')
            ->param('kodeBangsal', $kodeBangsal)
            ->setCounter(fn() => $this->getNextCounter('ri_' . $kodeBangsal, 'yearly'))
            ->romanSequence()
            ->generate();
    }
    
    private function getNextCounter(string $key, string $period): int
    {
        $db = \Config\Database::connect();
        // Implementation...
        return 1;
    }
}
```

## CI 3 Library Wrapper

Create `application/libraries/Numbering_lib.php`:

```php
<?php

require_once APPPATH . 'libraries/numbering/vendor/autoload.php';

use Wawaiguntang\Numbering\Numbering;

class Numbering_lib
{
    private Numbering $numbering;
    private $CI;
    
    public function __construct()
    {
        $this->CI =& get_instance();
        $this->CI->load->database();
        $this->numbering = new Numbering();
    }
    
    public function generatePoli($kodePoli = 'A')
    {
        return $this->numbering
            ->pattern('{prefix}{kodePoli}{Ym}{sequence:3}')
            ->param('prefix', 'PU')
            ->param('kodePoli', $kodePoli)
            ->setCounter(function() {
                $this->CI->db->where('counter_key', 'poli_umum');
                $this->CI->db->where('period', date('Y-m'));
                $query = $this->CI->db->get('numbering_counters');
                $record = $query->row();
                
                if (!$record) {
                    $this->CI->db->insert('numbering_counters', [
                        'counter_key' => 'poli_umum',
                        'last_number' => 1,
                        'period' => date('Y-m'),
                    ]);
                    return 1;
                }
                
                $this->CI->db->where('id', $record->id);
                $this->CI->db->update('numbering_counters', [
                    'last_number' => $record->last_number + 1,
                ]);
                
                return $record->last_number + 1;
            })
            ->generate();
    }
    
    public function generateIGD()
    {
        return $this->numbering
            ->pattern('IGD/{romanDate}/{sequence:2}')
            ->setCounter(function() {
                // Counter logic
                return 1;
            })
            ->generate();
    }
}
```

## Controller Usage (CI 4)

```php
<?php

namespace App\Controllers;

use App\Libraries\NumberingService;

class Pendaftaran extends BaseController
{
    public function daftar()
    {
        $numbering = new NumberingService();
        
        $data = [
            'no_registrasi' => $numbering->forPoliklinik('A'),
        ];
        
        // Save to database
        $this->pendaftaranModel->insert($data);
    }
}
```

## Controller Usage (CI 3)

```php
<?php

class Pendaftaran extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('Numbering_lib');
    }
    
    public function daftar()
    {
        $noRegistrasi = $this->numbering_lib->generatePoli('A');
        
        $data = [
            'no_registrasi' => $noRegistrasi,
        ];
        
        $this->db->insert('pendaftaran', $data);
    }
}
```

## Helper Function (CI 3/4)

Create `app/Helpers/numbering_helper.php`:

```php
<?php

use Wawaiguntang\Numbering\Numbering;

if (!function_exists('generate_number')) {
    function generate_number(string $pattern, callable $counter, array $params = []): string
    {
        $numbering = new Numbering($pattern);
        
        foreach ($params as $key => $value) {
            $numbering->param($key, $value);
        }
        
        return $numbering->setCounter($counter)->generate();
    }
}
```

Load in controller:

```php
helper('numbering');

// Usage
$noReg = generate_number(
    'PU/{kodePoli}{Ym}{sequence:3}',
    fn() => getNextCounter(),
    ['kodePoli' => 'A']
);
```
