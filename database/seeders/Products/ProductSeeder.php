<?php

namespace Database\Seeders\Products;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // Ambil data hewan dari tabel animals
        $animals = DB::table('animals')->get();

        foreach ($animals as $animal) {
            $stock = $animal->stock;
            $qurbanPortions = ceil($stock / 2); // Membagi stock menjadi dua produk

            // Insert Qurban Products
            for ($i = 0; $i < $qurbanPortions; $i++) {
                DB::table('qurban_products')->insert([
                    'animal_id' => $animal->id,
                    'name' => 'Qurban - ' . Str::random(5),
                    'description' => 'Qurban product description',
                    'price' => rand(5000000, 10000000), // Harga acak
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }

            // Insert Aqiqah Products
            for ($i = 0; $i < $stock - $qurbanPortions; $i++) {
                DB::table('aqiqah_products')->insert([
                    'animal_id' => $animal->id,
                    'name' => 'Aqiqah - ' . Str::random(5),
                    'description' => 'Aqiqah product description',
                    'price' => rand(3000000, 7000000), // Harga acak
                    'portion_count' => rand(1, 5), // Jumlah porsi acak
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }

        // Menambahkan 10 produk sadaqah
        for ($i = 1; $i <= 10; $i++) {
            DB::table('sadaqah_products')->insert([
                'name' => 'Sadaqah - ' . Str::random(5),
                'description' => 'Sadaqah product description for helping others.',
                'price' => rand(1000000, 5000000), // Harga acak
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        }
    }
}
