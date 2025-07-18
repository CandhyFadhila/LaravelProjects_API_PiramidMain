<?php

namespace Database\Seeders\Static;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            'Pending',
            'Paid',
            'Failed',
            'Expired',
            'Refunded',
            'Processing',
            'First Payment',
        ];

        foreach ($statuses as $status) {
            DB::table('payment_statuses')->insert([
                'label' => $status,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
