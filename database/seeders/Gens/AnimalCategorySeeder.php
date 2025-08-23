<?php

namespace Database\Seeders\Gens;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnimalCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $labels = ['Kambing', 'Sapi', 'Domba', 'Unta'];

        foreach ($labels as $label) {
            DB::table('animal_categories')->insert([
                'label' => $label,
                'for_aqiqah' => in_array($label, ['Kambing', 'Domba']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
