<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Super Admin',
                'number' => '0555555555',
                'country_code' => '+966',
                'iso_code' => 'SA',
                'password' => Hash::make('password'),
                'type' => 'admin',
            ]
        );
    }
}
