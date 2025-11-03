<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use App\Models\ClassifiedSector;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ClassifiedSectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sectors = collect([
            ['nom' => 'Services Financiers', 'slug' => "Services Financiers"],
            ['nom' => 'Télécommunications', 'slug' => "Télécommunications"],
            ['nom' => 'Services publics', 'slug' => "Services publics"],
            ['nom' => 'Pétrole et Energie', 'slug' => "Pétrole et Energie"],
            ['nom' => 'Biens de consommation', 'slug' => "Biens de consommation"],
            ['nom' => 'Agro Industrie', 'slug' => "Agro Industrie"],
            ['nom' => 'Industrie', 'slug' => "Industrie"],
            ['nom' => 'Logistique', 'slug' => "Logistique"],
            ['nom' => 'BTP', 'slug' => "BTP"],
            ['nom' => 'Automobile ', 'slug' => "Automobile "],
            ['nom' => 'Consommation discrétionnaire ', 'slug' => "Consommation discrétionnaire "],
        ]);

            // On vide la table avant d’insérer les nouvelles données
            DB::table('classified_sectors')->delete();
            // Insertion propre et rapide
            $sectors->each(fn($sector) =>
            ClassifiedSector::create(array_merge($sector, [
                    'key' => 'cla_' . strtolower(Str::random(8)),
                ]))
            );

    }
}
