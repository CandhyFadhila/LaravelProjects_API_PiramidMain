<?php

namespace Database\Seeders\Auth;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $dates = Carbon::now('Asia/Jakarta');
        $super_admin_account = User::create([
            'name' => 'Super Admin',
            'email' => 'studio.exium@gmail.com',
            'password' => Hash::make('superadmin123'),
            'account_status' => 2,
            'register_at' => $dates,
            'created_at' => $dates,
            'updated_at' => $dates
        ]);

        $super_admin_account->assignRole('Super Admin');

        $userMarketplace = User::create([
            'name' => 'Marketplace',
            'email' => 'distrostudiodev@gmail.com',
            'password' => Hash::make('marketplace123'),
            'account_status' => 2,
            'register_at' => $dates,
            'created_at' => $dates,
            'updated_at' => $dates
        ]);

        $userMarketplace->assignRole('Marketplace');
    }
}
