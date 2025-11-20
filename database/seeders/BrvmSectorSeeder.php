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
            ['nom' => 'Consommation de base', 'slug' => Str::slug("Consommation de base")],
            ['nom' => 'Consommation discrétionnaire', 'slug' => Str::slug("Consommation discrétionnaire")],
            ['nom' => 'Santé', 'slug' => Str::slug("Santé")],
            ['nom' => 'Technologie de l\'information', 'slug' => Str::slug("Technologie de l'information")],
            ['nom' => 'Matériaux', 'slug' => Str::slug("Matériaux")],
            ['nom' => 'Immobilier', 'slug' => Str::slug("Immobilier")],
            ['nom' => 'Services de communication', 'slug' => Str::slug("Services de communication")],
            ['nom' => 'Services aux consommateurs', 'slug' => Str::slug("Services aux consommateurs")],
            ['nom' => 'Transport', 'slug' => Str::slug("Transport")],
            ['nom' => 'Énergie', 'slug' => Str::slug("Énergie")],
            ['nom' => 'Services', 'slug' => Str::slug("Services")],
            ['nom' => 'Industriels', 'slug' => Str::slug("Industriels")],
            ['nom' => 'Services financiers', 'slug' => Str::slug("Services financiers")],
            ['nom' => 'Services publics', 'slug' => Str::slug("Services publics")],
            ['nom' => 'Télécommunications', 'slug' => Str::slug("Télécommunications")],
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
