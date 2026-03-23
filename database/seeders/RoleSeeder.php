<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
{
    $roles = [
        [
            'name' => 'Admin',
            'description' => 'Have all powers to the system.',
            'guard_name' => 'web'
        ],
        [
            'name' => 'Shop Owner',
            'description' => 'Own shops.',
            'guard_name' => 'web'
        ],
        [
            'name' => 'Manager',
            'description' => 'Manage shops.',
            'guard_name' => 'web'
        ],
        [
            'name' => 'Worker',
            'description' => 'Works at the shop.',
            'guard_name' => 'web'
        ],
    ];

    foreach ($roles as $role) {
        // Use Spatie's Role model or ensure your custom model extends it
        \Spatie\Permission\Models\Role::firstOrCreate(
            ['name' => $role['name'], 'guard_name' => $role['guard_name']],
            ['description' => $role['description']]
        );
    }
}

}
