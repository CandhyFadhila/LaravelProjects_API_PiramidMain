<?php

namespace Database\Seeders\Auth;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $created_at = Carbon::now()->subDays(rand(1, 30));
        $updated_at = Carbon::now()->subDays(rand(1, 30));

        $SuperAdmin = Role::create([
            'name' => 'Super Admin',
            'description' => 'Ini adalah role Super Admin',
            'guard_name' => 'web',
            'created_at' => $created_at,
            'updated_at' => $updated_at
        ]);

        $SuperAdmin->givePermissionTo(Permission::all());

        Role::create([
            'name' => 'Marketplace',
            'description' => 'Ini adalah role Marketplace',
            'guard_name' => 'web',
            'created_at' => $created_at,
            'updated_at' => $updated_at
        ]);
    }
}
