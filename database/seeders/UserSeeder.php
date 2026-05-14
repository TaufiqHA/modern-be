<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Admin User
        User::updateOrCreate(
            ['email' => 'admin@modernstore.com'], // Unique identifier
            [
                'name' => 'System Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'phone' => '08123456789',
            ]
        );

        // 2. Create Regular User
        User::updateOrCreate(
            ['email' => 'user@modernstore.com'], // Unique identifier
            [
                'name' => 'Standard User',
                'password' => Hash::make('password'),
                'role' => 'user',
                'phone' => '08987654321',
            ]
        );
    }
}
