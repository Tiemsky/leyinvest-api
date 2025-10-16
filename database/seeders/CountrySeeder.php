<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Str;

class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Liste des pays de l'UEMOA
        $countries = [
            ['name' => "Côte d'Ivoire", 'slug' =>Str::slug("Côte d'Ivoire")],
            ['name' => "Bénin", 'slug' =>Str::slug("Bénin")],
            ['name' => "Burkina Faso", 'slug' =>Str::slug("Burkina Faso")],
            ['name' => "Guinée-Bissau", 'slug' =>Str::slug("Guinée-Bissau")],
            ['name' => "Mali", 'slug' =>Str::slug("Mali")],
            ['name' => "Niger", 'slug' =>Str::slug("Niger")],
            ['name' => "Sénégal", 'slug' =>Str::slug("Sénégal")],
            ['name' => "Togo", 'slug' =>Str::slug("Togo")],
        ];

        Country::truncate();
        foreach($countries as $country){
            Country::create($country);
        }
    }
}
