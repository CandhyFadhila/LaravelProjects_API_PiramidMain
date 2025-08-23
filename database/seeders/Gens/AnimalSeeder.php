<?php

namespace Database\Seeders\Gens;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnimalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Ambil id kategori berdasarkan label
        $sapiId     = DB::table('animal_categories')->where('label', 'Sapi')->value('id');
        $kambingId  = DB::table('animal_categories')->where('label', 'Kambing')->value('id');
        $dombaId    = DB::table('animal_categories')->where('label', 'Domba')->value('id');
        $untaId     = DB::table('animal_categories')->where('label', 'Unta')->value('id');

        // Ambil id ras berdasarkan label
        $breeds = DB::table('animal_breeds')->pluck('id', 'label');

        $animals = [
            [
                'animal_category_id' => $sapiId,
                'animal_breed_id'    => $breeds['Sapi Bali'],
                'average_weight'     => 250,
                'birth_date'         => Carbon::now()->subMonths(12),
                'stock'              => 10,
            ],
            [
                'animal_category_id' => $sapiId,
                'animal_breed_id'    => $breeds['Sapi Ongoles'],
                'average_weight'     => 400,
                'birth_date'         => Carbon::now()->subMonths(14),
                'stock'              => 7,
            ],
            [
                'animal_category_id' => $kambingId,
                'animal_breed_id'    => $breeds['Kambing Boer'],
                'average_weight'     => 45,
                'birth_date'         => Carbon::now()->subMonths(8),
                'stock'              => 15,
            ],
            [
                'animal_category_id' => $dombaId,
                'animal_breed_id'    => $breeds['Domba Persilangan (Crossbreed)'],
                'average_weight'     => 55,
                'birth_date'         => Carbon::now()->subMonths(10),
                'stock'              => 12,
            ],
            [
                'animal_category_id' => $untaId,
                'animal_breed_id'    => $breeds['Unta Al-irab'],
                'average_weight'     => 120,
                'birth_date'         => Carbon::now()->subMonths(4),
                'stock'              => 7,
            ],
        ];

        foreach ($animals as $animal) {
            DB::table('animals')->insert([
                ...$animal,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
