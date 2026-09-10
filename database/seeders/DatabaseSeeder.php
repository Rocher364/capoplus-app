<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder senp pou kreye kont yo si yo pa egziste
        if (!User::where('email', 'romererocher@gmail.com')->exists()) {
            User::create([
                'name' => 'Admin Rocher',
                'email' => 'romererocher@gmail.com',
                'password' => Hash::make('toudji@12#'),
            ]);
        }

        if (!User::where('email', 'steeve@gmail.com')->exists()) {
            User::create([
                'name' => 'Directeur Steeve',
                'email' => 'steeve@gmail.com',
                'password' => Hash::make('toudji1234'),
            ]);
        }
    }
}
