<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Place;
use App\Models\Category;
use App\Models\User;
use Illuminate\Support\Str;

class PlaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener un usuario 'partner' para asignar los lugares
        $partner = User::where('role', 'partner')->first();
        // Obtener todas las categorías
        $categories = Category::all();

        if (!$partner || $categories->isEmpty()) {
            return;
        }

        $places = [
            [
                'name' => 'El Salto del Ángel',
                'description' => 'La cascada más alta del mundo, ubicada en el Parque Nacional Canaima.',
                'short_description' => 'Cascada impresionante en Venezuela.',
                'address' => 'Parque Nacional Canaima, Bolívar',
            ],
            [
                'name' => 'Montaña de Sorte',
                'description' => 'Monumento natural y lugar de peregrinación espiritual.',
                'short_description' => 'Montaña mística en Yaracuy.',
                'address' => 'Chivacoa, Yaracuy',
            ],
            [
                'name' => 'Playa Colorada',
                'description' => 'Hermosa playa de arenas rojizas en el Parque Nacional Mochima.',
                'short_description' => 'Playa paradisíaca.',
                'address' => 'Parque Nacional Mochima, Sucre',
            ],
            [
                'name' => 'Pico Bolívar',
                'description' => 'El pico más alto de Venezuela, cubierto de nieves perpetuas.',
                'short_description' => 'Pico nevado en los Andes.',
                'address' => 'Sierra Nevada, Mérida',
            ],
            [
                'name' => 'Cueva del Guácharo',
                'description' => 'Impresionante cueva habitada por aves nocturnas llamadas guácharos.',
                'short_description' => 'Monumento natural espeleológico.',
                'address' => 'Caripe, Monagas',
            ],
        ];

        foreach ($places as $data) {
            Place::create([
                'user_id' => $partner->id,
                'category_id' => $categories->random()->id,
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . uniqid(),
                'short_description' => $data['short_description'],
                'description' => $data['description'],
                'address' => $data['address'],
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
                'status' => 'approved', // Crear como aprobados para prueba
                'is_featured' => fake()->boolean(30), // 30% de probabilidad de ser destacado
            ]);
        }
    }
}
