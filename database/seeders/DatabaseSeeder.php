<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear usuario Admin
        User::firstOrCreate(
            ['email' => 'admin@ecoaventura.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Crear usuario Partner (socio)
        User::firstOrCreate(
            ['email' => 'partner@ecoaventura.com'],
            [
                'name' => 'Socio Demo',
                'password' => Hash::make('password'),
                'role' => 'partner',
            ]
        );

        // Crear usuario normal
        User::firstOrCreate(
            ['email' => 'user@ecoaventura.com'],
            [
                'name' => 'Usuario Demo',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );

        // Ejecutar seeders adicionales
        $this->call([
            CategorySeeder::class,
        ]);
    }
}
