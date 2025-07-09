<?php

namespace Database\Seeders\Gens\CoverageArea;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProvinceCitySeeder extends Seeder
{
    public function run()
    {
        $provinces = [
            'Aceh',
            'Bali',
            'Banten',
            'Bengkulu',
            'Gorontalo',
            'Jakarta',
            'Jambi',
            'Jawa Barat',
            'Jawa Tengah',
            'Jawa Timur',
            'Kalimantan Barat',
            'Kalimantan Selatan',
            'Kalimantan Tengah',
            'Kalimantan Timur',
            'Kalimantan Utara',
            'Kepulauan Riau',
            'Lampung',
            'Maluku',
            'Maluku Utara',
            'Nusa Tenggara Barat',
            'Nusa Tenggara Timur',
            'Papua',
            'Papua Barat',
            'Riau',
            'Sulawesi Barat',
            'Sulawesi Selatan',
            'Sulawesi Tengah',
            'Sulawesi Tenggara',
            'Sulawesi Utara',
            'Sumatera Barat',
            'Sumatera Selatan',
            'Sumatera Utara',
            'Yogyakarta'
        ];

        // Menambahkan provinsi
        foreach ($provinces as $province) {
            $provinceId = DB::table('provinces')->insertGetId([
                'name' => $province,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);

            // Data kota berdasarkan provinsi
            $cities = $this->getCitiesForProvince($province);

            // Menambahkan kota-kota terkait provinsi
            foreach ($cities as $city) {
                DB::table('cities')->insert([
                    'province_id' => $provinceId,
                    'name' => $city,
                    'is_active' => true,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
            }
        }
    }

    // Fungsi untuk mengambil kota-kota berdasarkan provinsi
    private function getCitiesForProvince($province)
    {
        $cities = [
            'Aceh' => ['Banda Aceh', 'Lhokseumawe', 'Langsa'],
            'Bali' => ['Denpasar', 'Badung', 'Singaraja'],
            'Banten' => ['Serang', 'Tangerang', 'Cilegon'],
            'Bengkulu' => ['Bengkulu', 'Rejang Lebong'],
            'Gorontalo' => ['Gorontalo', 'Bone Bolango'],
            'Jakarta' => ['Jakarta Pusat', 'Jakarta Utara', 'Jakarta Timur'],
            'Jambi' => ['Jambi', 'Muaro Jambi'],
            'Jawa Barat' => ['Bandung', 'Bogor', 'Bekasi'],
            'Jawa Tengah' => ['Semarang', 'Solo', 'Magelang'],
            'Jawa Timur' => ['Surabaya', 'Malang', 'Madiun'],
            'Kalimantan Barat' => ['Pontianak', 'Singkawang'],
            'Kalimantan Selatan' => ['Banjarmasin', 'Banjarbaru'],
            'Kalimantan Tengah' => ['Palangka Raya', 'Kuala Kapuas'],
            'Kalimantan Timur' => ['Samarinda', 'Balikpapan'],
            'Kalimantan Utara' => ['Tanjung Selor', 'Tarakan'],
            'Kepulauan Riau' => ['Batam', 'Tanjung Pinang'],
            'Lampung' => ['Bandar Lampung', 'Metro'],
            'Maluku' => ['Ambon', 'Tual'],
            'Maluku Utara' => ['Ternate', 'Tidore'],
            'Nusa Tenggara Barat' => ['Mataram', 'Bima'],
            'Nusa Tenggara Timur' => ['Kupang', 'Ende'],
            'Papua' => ['Jayapura', 'Sorong'],
            'Papua Barat' => ['Manokwari', 'Sorong'],
            'Riau' => ['Pekanbaru', 'Dumai'],
            'Sulawesi Barat' => ['Mamuju', 'Polewali Mandar'],
            'Sulawesi Selatan' => ['Makassar', 'Parepare'],
            'Sulawesi Tengah' => ['Palu', 'Donggala'],
            'Sulawesi Tenggara' => ['Kendari', 'Bau-Bau'],
            'Sulawesi Utara' => ['Manado', 'Bitung'],
            'Sumatera Barat' => ['Padang', 'Bukittinggi'],
            'Sumatera Selatan' => ['Palembang', 'Lubuklinggau'],
            'Sumatera Utara' => ['Medan', 'Binjai'],
            'Yogyakarta' => ['Yogyakarta', 'Sleman']
        ];

        return $cities[$province] ?? [];
    }
}
