<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class MakeTraitCommand extends Command
{
    // Kita ubah signature agar mudah dipanggil: php artisan make:trait NamaTrait
    protected $signature = 'make:trait {name}';

    protected $description = 'Membuat file Trait baru';

    public function handle()
    {
        $name = $this->argument('name');
        $path = app_path("Traits/{$name}.php");

        // Membuat direktori jika belum ada
        if (!File::isDirectory(app_path('Traits'))) {
            File::makeDirectory(app_path('Traits'), 0777, true, true);
        }

        if (File::exists($path)) {
            $this->error("Trait {$name} sudah ada!");
            return;
        }

        // Template konten Trait
        $content = "<?php\n\nnamespace App\Traits;\n\ntrait {$name}\n{\n    // Logic anda di sini\n}\n";

        File::put($path, $content);

        $this->info("Trait {$name} berhasil dibuat di app/Traits/{$name}.php");
    }
}
