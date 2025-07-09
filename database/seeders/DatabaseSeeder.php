<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Auth\AccountSeeder;
use Database\Seeders\Auth\PermissionSeeder;
use Database\Seeders\Auth\RoleSeeder;
use Database\Seeders\Gens\AnimalBreedSeeder;
use Database\Seeders\Gens\AnimalCategorySeeder;
use Database\Seeders\Gens\AnimalSeeder;
use Database\Seeders\Gens\CoverageArea\ProvinceCitySeeder;
use Database\Seeders\Gens\MosqueRelationSeeder;
use Database\Seeders\Products\ProductSeeder;
use Database\Seeders\Static\PaymentMethodSeeder;
use Database\Seeders\Static\PaymentStatusSeeder;
use Database\Seeders\Static\ServiceTypeSeeder;
use Database\Seeders\Static\TransactionStatusSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AccountSeeder::class,

            // Static Data
            PaymentMethodSeeder::class,
            PaymentStatusSeeder::class,
            ServiceTypeSeeder::class,
            TransactionStatusSeeder::class,

            // Gens
            AnimalCategorySeeder::class,
            AnimalBreedSeeder::class,
            AnimalSeeder::class,
            MosqueRelationSeeder::class,
            ProvinceCitySeeder::class,

            // Products
            ProductSeeder::class
        ]);
    }
}
