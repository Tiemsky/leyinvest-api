<?php

namespace Database\Seeders;

use App\Models\BocIndicator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class BocIndicatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        BocIndicator::truncate();
        DB::table('boc_indicators')->insert([
            'date_rapport' => '2025-11-07',
            'taux_rendement_moyen' => 7.85,
            'key' => 'boc_ley20251024',
            'per_moyen' => 11.74,
            'taux_rentabilite_moyen' => 9.94,
            'prime_risque_marche' => 2.77,
            'source_pdf' => 'https://www.brvm.org/sites/default/files/boc_20251107_2.pdf',
            'created_at' => '2025-01-07 07:19:38',
            'updated_at' => now(),
        ]);
    }
}
