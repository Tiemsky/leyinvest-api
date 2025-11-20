<?php

namespace Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class PositionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $positions = [
            ['nom' => "PCA", 'description' =>Str::slug("President du conseil d'administration")],
            ['nom' => "DG", 'description' =>Str::slug("President directeur général")],
            ['nom' => "DAF", 'description' =>Str::slug("Directeur Administratif et Financier")],
            ['nom' => "Directeur Marketing/Commercial", 'description' =>Str::slug("Directeur Marketing/Commercial")],
        ];
        // On vide la table
        DB::table('positions')->delete();

        foreach ($positions as $position) {
            DB::table('positions')->insert([
                'nom' => $position['nom'],
                'description' => $position['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
