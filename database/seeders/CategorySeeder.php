<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Avistamiento de aves',
            'Senderismo',
            'Paisaje cultural cafetero',
            'Termales',
            'Nevados y montañas',
            'Cascadas',
            'Glamping',
            'Parques temáticos',
            'Rios y lagos',
            'Miradores',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );
        }
    }
}
