<?php

namespace Database\Seeders\Gens;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MosqueRelationSeeder extends Seeder
{
    public function run()
    {
        $cities = [
            ['Surabaya', 'Jawa Timur'],
            ['Bandung', 'Jawa Barat'],
            ['Medan', 'Sumatera Utara'],
            ['Makassar', 'Sulawesi Selatan'],
            ['Palembang', 'Sumatera Selatan'],
            ['Semarang', 'Jawa Tengah'],
            ['Jakarta', 'DKI Jakarta'],
            ['Yogyakarta', 'DI Yogyakarta'],
            ['Denpasar', 'Bali'],
            ['Balikpapan', 'Kalimantan Timur']
        ];

        for ($i = 1; $i <= 30; $i++) {
            $cityData = $cities[array_rand($cities)];
            DB::table('mosques')->insert([
                'name' => 'Masjid ' . Str::random(5),
                'phone_number' => '08' . rand(1111111111, 9999999999),
                'wa_number' => rand(0, 1) ? '08' . rand(1111111111, 9999999999) : null,
                'address' => 'Jl. ' . Str::random(10) . ' No.' . rand(1, 100),
                'city' => $cityData[0],
                'province' => $cityData[1],
                'postal_code' => rand(10000, 99999),
                'country' => 'Indonesia',
                'latitude' => '-' . rand(60, 80) . '.' . rand(100000, 999999),
                'longitude' => rand(100, 120) . '.' . rand(100000, 999999),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
