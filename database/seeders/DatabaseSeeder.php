<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Category;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear usuario ADMIN
        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        // 2. Crear usuario PARTNER (Socio)
        User::factory()->create([
            'name' => 'Socio EcoAventura',
            'email' => 'partner@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'partner',
        ]);

        // 3. Crear usuario USER (Normal)
        User::factory()->create([
            'name' => 'Usuario Test',
            'email' => 'user@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'user',
        ]);

        // 4. Llamar al Seeder de Categorías
        $this->call(CategorySeeder::class);

        // 5. Llamar al Seeder de Lugares
        $this->call(PlaceSeeder::class);
    }
}
