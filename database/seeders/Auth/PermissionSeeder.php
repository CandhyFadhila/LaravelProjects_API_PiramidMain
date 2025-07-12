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
            // Master Permission
            ['name' => 'only-super-admin', 'description' => 'Only Super Admin', 'group' => 'master_permission'],


            // Master Data
            ['name' => 'masterdata.view', 'description' => 'View Master Data', 'group' => 'master_data'],
            ['name' => 'masterdata.create', 'description' => 'Create Master Data', 'group' => 'master_data'],
            ['name' => 'masterdata.edit', 'description' => 'Edit Master Data', 'group' => 'master_data'],
            ['name' => 'masterdata.delete', 'description' => 'Delete Master Data', 'group' => 'master_data'],
            ['name' => 'masterdata.restore', 'description' => 'Restore Master Data', 'group' => 'master_data'],

            // Master Setting
            ['name' => 'mastersetting.view', 'description' => 'View Master Setting', 'group' => 'master_setting'],
            ['name' => 'mastersetting.create', 'description' => 'Create Master Setting', 'group' => 'master_setting'],
            ['name' => 'mastersetting.edit', 'description' => 'Edit Master Setting', 'group' => 'master_setting'],
            ['name' => 'mastersetting.delete', 'description' => 'Delete Master Setting', 'group' => 'master_setting'],
            ['name' => 'mastersetting.restore', 'description' => 'Restore Master Setting', 'group' => 'master_setting'],

            // Service
            ['name' => 'transaction.view', 'description' => 'View Transaction Data', 'group' => 'transaction_service'],
            ['name' => 'transaction.create', 'description' => 'Create Transaction Data', 'group' => 'transaction_service'],
            ['name' => 'transaction.edit', 'description' => 'Edit Transaction Data', 'group' => 'transaction_service'],
            ['name' => 'transaction.delete', 'description' => 'Delete Transaction Data', 'group' => 'transaction_service'],
            ['name' => 'transaction.restore', 'description' => 'Restore Transaction Data', 'group' => 'transaction_service'],
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
