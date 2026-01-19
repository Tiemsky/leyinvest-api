<?php

namespace Database\Seeders;

use App\Models\Top;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tops = [
            ['symbole' => 'ABJC', 'cours' => 2265, 'variation' => 7.35],
            ['symbole' => 'PRSC', 'cours' => 3710, 'variation' => 3.06],
            ['symbole' => 'SOGC', 'cours' => 8890, 'variation' => 2.07],
            ['symbole' => 'NEIC', 'cours' => 700,  'variation' => 1.45],
            ['symbole' => 'SPHC', 'cours' => 8100, 'variation' => 1.44],
        ];

        Top::truncate();
        foreach ($tops as $top) {
            Top::create(array_merge($top, [
                'key' => 'top_'.strtolower(Str::random(8)),
            ]));
        }

    }
}
