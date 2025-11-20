<?php

namespace Database\Seeders;

use App\Models\Flop;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FlopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $flops = [
            ['symbole' => 'CFAC', 'cours' => 1565, 'variation' => -7.40],
            ['symbole' => 'SAFC', 'cours' => 2470, 'variation' => -7.32],
            ['symbole' => 'ONTBF', 'cours' => 2370, 'variation' => -6.14],
            ['symbole' => 'ECOC', 'cours' => 13750, 'variation' => -3.85],
            ['symbole' => 'SIVC', 'cours' => 690,  'variation' => -3.50],
        ];

        Flop::truncate();
        foreach($flops as $flop){
            Flop::create(array_merge($flop, [
                'key' => 'flop_' . strtolower(Str::random(8)),
            ]));
        }
    }
}
