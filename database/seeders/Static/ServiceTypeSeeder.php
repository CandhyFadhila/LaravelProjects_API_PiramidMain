<?php

namespace Database\Seeders\Static;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $labels = ['Qurban', 'Aqiqah', 'Sadaqah'];
        foreach ($labels as $label) {
            DB::table('service_types')->insert([
                'label' => $label,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
