<?php

namespace Database\Seeders;

use App\Models\BrvmSector;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrvmSectorSeeder extends Seeder
{
    /**
     * Exécute le seeder.
     */
    public function run(): void
    {
        $sectors = collect([
            ['nom' => 'Consommation de base', 'slug' => 'consommation-de-base'],
            ['nom' => 'Consommation discrétionnaire', 'slug' => 'consommation-discretionnaire'],
            ['nom' => 'Énergie', 'slug' => 'energie'],
            ['nom' => 'Industriels', 'slug' => 'industriels'],
            ['nom' => 'Services financiers', 'slug' => 'services-financiers'],
            ['nom' => 'Services publics', 'slug' => 'services-publics'],
            ['nom' => 'Télécommunications', 'slug' => 'telecommunications'],
        ]);

        // Nettoyage avant réinsertion
        DB::table('brvm_sectors')->delete();

        // Insertion rapide et propre
        $sectors->each(fn ($sector) =>
            BrvmSector::create(array_merge($sector, [
                'key' => 'brv_' . strtolower(Str::random(8)),
            ]))
        );
    }
}
