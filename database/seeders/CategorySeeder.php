<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Seed las categorías iniciales de ecoAventura
     */
    public function run(): void
    {
        $categories = [
            'Senderismo',
            'Camping',
            'Playas',
            'Cascadas',
            'Montañas',
            'Ríos',
            'Lagos',
            'Bosques',
            'Parques Naturales',
            'Reservas Ecológicas',
            'Miradores',
            'Cuevas',
            'Deportes Extremos',
            'Observación de Aves',
            'Turismo Rural',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
