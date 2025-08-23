<?php

namespace Database\Seeders\Gens;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnimalBreedSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $labels = [
            'Sapi Bali',
            'Sapi Madura',
            'Sapi Ongoles',
            'Sapi Brahman',
            'Sapi Limousin',
            'Sapi Simmental',
            'Kambing Etawa',
            'Kambing Kacang',
            'Kambing Boer',
            'Kambing Jawarandu',
            'Kambing Garut',
            'Kambing Samosir',
            'Domba Ekor Gemuk',
            'Domba Ekor Tipis',
            'Domba Texel',
            'Domba Merino',
            'Domba Dorper',
            'Domba Persilangan (Crossbreed)',
            'Unta Al-irab',
            'Unta Al-bakhati',
        ];

        foreach ($labels as $label) {
            DB::table('animal_breeds')->insert([
                'label' => $label,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
