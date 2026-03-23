<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset cached roles and permissions (Crucial for Spatie)
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Define your permissions
        $permissions = [
            // Shop Management
            'view shops',
            'create shops',
            'edit shops',
            'delete shops',

            // Worker/Staff Management
            'manage workers',
            'view reports',

            // User Management (System Level)
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',

            //Shops
            'view shops',
            'create shops',
            'edit shops',
            'delete shops',
            'view reports',
        ];


        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web'
            ]);
        }

        // 3. Assign Permissions to Roles (Optional but Recommended)
        $adminRole = Role::findByName('Admin');
        $adminRole->givePermissionTo(Permission::all());

        $ownerRole = Role::findByName('Shop Owner');
        $ownerRole->givePermissionTo(['view shops', 'create shops', 'edit shops', 'manage workers']);

        $managerRole = Role::findByName('Manager');
        $managerRole->givePermissionTo(['view shops', 'edit shops', 'manage workers']);
    }
}
