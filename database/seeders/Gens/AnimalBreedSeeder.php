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
            'Sapi Limousin',
            'Sapi PO',
            'Kambing Etawa',
            'Kambing Kacang',
            'Domba Garut',
            'Domba Texel',
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
