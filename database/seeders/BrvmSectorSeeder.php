<?php

namespace Database\Seeders;

use App\Models\BrvmSector;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrvmSectorSeeder extends Seeder
{

    public function run(): void
    {
        $sectors = collect([
            [
                "nom"       => 'Consommation de base',
                "slug"      => Str::slug("Consommation de base"),
                "variation" => -0.18
            ],
            [
                "nom"       => "Consommation discrétionnaire",
                "slug"      => Str::slug("Consommation discrétionnaire"),
                "variation" => 0.38
            ],
            [
                "nom"        => "Énergie",
                "slug"       => Str::slug("Énergie"),
                "variation"  => -0.95
            ],
            [
                "nom"        => "Industriels",
                "slug"       => Str::slug("Industriels"),
                "variation"  => 0.57
            ],
            [
                "nom"       => "Services financiers",
                "slug"      => Str::slug("Services financiers"),
                "variation" => -0.41
            ],
            [
                "nom"       => "Services publics",
                "slug"      => Str::slug("Services publics"),
                "variation" => 0.00
            ],
            [
                "nom"        => "Télécommunications",
                "slug"       => Str::slug("Télécommunications"),
                "variation"  => 0.20
            ],
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
