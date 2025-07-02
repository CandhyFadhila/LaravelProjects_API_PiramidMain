<?php

namespace Database\Seeders\Auth;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Master Data
            ['name' => 'masterdata.view', 'description' => 'View Master Data', 'group' => 'master_data'],
            ['name' => 'masterdata.create', 'description' => 'Create Master Data', 'group' => 'master_data'],
            ['name' => 'masterdata.edit', 'description' => 'Edit Master Data', 'group' => 'master_data'],
            ['name' => 'masterdata.delete', 'description' => 'Delete Master Data', 'group' => 'master_data'],
            ['name' => 'masterdata.restore', 'description' => 'Restore Master Data', 'group' => 'master_data'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission['name'],
                'guard_name' => 'web',
            ], [
                'description' => $permission['description'],
                'group' => $permission['group'],
            ]);
        }
    }
}
