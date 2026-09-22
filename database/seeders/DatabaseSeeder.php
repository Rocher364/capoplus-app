<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $comptes = [
            [
                'name' => env('ADMIN_NAME', 'Administrateur'),
                'email' => env('ADMIN_EMAIL'),
                'password' => env('ADMIN_PASSWORD'),
                'role' => 'admin',
            ],
            [
                'name' => env('DIRECTOR_NAME', 'Directeur'),
                'email' => env('DIRECTOR_EMAIL'),
                'password' => env('DIRECTOR_PASSWORD'),
                'role' => 'auditeur',
            ],
        ];

        foreach ($comptes as $compte) {
            if (! $compte['email'] || ! $compte['password']) {
                continue;
            }

            $user = User::firstOrNew(['email' => $compte['email']]);
            $user->forceFill([
                'name' => $compte['name'],
                'role' => $compte['role'],
                'statut' => 'actif',
            ]);

            if (! $user->exists) {
                $user->password = Hash::make($compte['password']);
            }

            $user->save();
        }
    }
}
