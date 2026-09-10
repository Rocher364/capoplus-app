<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Kont Admin
        User::updateOrCreate(
            ['email' => 'romererocher@gmail.com'],
            [
                'name' => 'Admin Rocher',
                'password' => Hash::make('toudji@12#'),
            ]
        );

        // Kont Directeur
        User::updateOrCreate(
            ['email' => 'steeve@gmail.com'],
            [
                'name' => 'Directeur Steeve',
                'password' => Hash::make('toudji1234'),
            ]
        );
    }
}
