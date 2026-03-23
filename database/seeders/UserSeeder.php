<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'Admin',
                'middle_name' => 'Admin',
                'last_name' => 'Admin',
                'email' => 'benijohn@gmail.com',
                'phone' => '1234567890',
                'password' => bcrypt('1234'),
            ],
        ];

        foreach ($users as $userData) {
            // Capture the model instance in $user
            $user = \App\Models\User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            // This works because $user is now a Model, not an array
            $user->assignRole('Admin');
        }
    }
    

}
