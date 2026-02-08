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
        // Obtener un usuario 'partner' o el primero disponible para asignar los lugares
        $partner = User::where('role', 'partner')->first() ?? User::first();

        if (!$partner) {
            return;
        }

        $places = [
            [
                'name' => 'Santuario de Fauna y Flora Otún Quimbaya',
                'category_slug' => 'avistamiento-de-aves',
                'short_description' => 'Santuario con selva húmeda andina y centro de biodiversidad de aves.',
                'description' => "Santuario con selva húmeda andina y uno de los mayores centros de biodiversidad de aves y fauna del país.\n\nActividades:\n- Senderos ecológicos: “El Humedal”, “Bejucos”, “El Río”.\n- Avistamiento de aves: Más de 300 especies.\n- Observación de fauna: Monos aulladores, pavas caucanas.\n- Interpretación ambiental guiada.",
                'address' => 'Pereira, Risaralda',
                'latitude' => 4.800201,
                'longitude' => -75.616333,
                'difficulty' => 'media',
                'duration' => '4–6 horas',
                'best_season' => 'Septiembre–noviembre / enero–marzo',
                'images' => [
                    'https://picsum.photos/800/600?random=1',
                    'https://picsum.photos/800/600?random=2'
                ]
            ],
            [
                'name' => 'Laguna del Otún',
                'category_slug' => 'nevados-y-montanas',
                'short_description' => 'Laguna glaciar rodeada de frailejones en el PNN Los Nevados.',
                'description' => "Laguna de origen glaciar en el PNN Los Nevados, rodeada de frailejones y fauna alpina.\n\nActividades:\n- Trekking alto en montaña.\n- Avistamiento de aves de altura: Patos andinos.\n- Pesca recreativa de trucha.\n- Fotografía de paisajes y páramo.",
                'address' => 'Santa Rosa de Cabal / Pereira',
                'latitude' => 4.750555,
                'longitude' => -75.250444,
                'difficulty' => 'alta',
                'duration' => '6–10 horas',
                'best_season' => 'Diciembre–marzo',
                'images' => [
                    'https://picsum.photos/800/600?random=3',
                    'https://picsum.photos/800/600?random=4'
                ]
            ],
            [
                'name' => 'Parque Nacional Natural Tatamá',
                'category_slug' => 'nevados-y-montanas',
                'short_description' => 'Área protegida con páramo virgen y biodiversidad única.',
                'description' => "Área protegida con páramo virgen, ecosistemas intactos y biodiversidad única.\n\nActividades:\n- Caminatas de alta montaña exigentes.\n- Exploración de páramo.\n- Observación de aves y vida silvestre.",
                'address' => 'Santuario, Risaralda',
                'latitude' => 4.800000,
                'longitude' => -75.890000,
                'difficulty' => 'experto',
                'duration' => '2–3 días',
                'best_season' => 'Junio–agosto / diciembre–marzo',
                'images' => [
                    'https://picsum.photos/800/600?random=5',
                    'https://picsum.photos/800/600?random=6'
                ]
            ],
            [
                'name' => 'Parque Municipal Natural Planes de San Rafael',
                'category_slug' => 'rios-y-lagos',
                'short_description' => 'Bosque andino protector, puerta de entrada al PNN Tatamá.',
                'description' => "Bosque andino protector de microcuenca y biodiversidad, puerta de entrada al PNN Tatamá.\n\nActividades:\n- Senderismo interpretativo siguiendo el Río San Rafael.\n- Avistamiento de aves de bosques húmedos.\n- Educación ambiental comunitaria.",
                'address' => 'Santuario, Risaralda',
                'latitude' => 4.650000,
                'longitude' => -75.700000,
                'difficulty' => 'media',
                'duration' => '3–5 horas',
                'best_season' => 'Enero–marzo / julio–agosto',
                'images' => [
                    'https://picsum.photos/800/600?random=7',
                    'https://picsum.photos/800/600?random=8'
                ]
            ],
            [
                'name' => 'Santuario Ecoturístico de Barcinal',
                'category_slug' => 'cascadas',
                'short_description' => 'Bosques, humedales y avistamiento de aves en Mistrató.',
                'description' => "Lugar con bosques, humedales y avistamiento de aves en Mistrató.\n\nActividades:\n- Observación de aves (más de 500 especies).\n- Caminatas por senderos naturales, quebradas y cascadas.",
                'address' => 'Mistrató, Risaralda',
                'latitude' => 4.850000,
                'longitude' => -75.820000,
                'difficulty' => 'media',
                'duration' => '2–4 horas',
                'best_season' => 'Temporada seca',
                'images' => [
                    'https://picsum.photos/800/600?random=9',
                    'https://picsum.photos/800/600?random=10'
                ]
            ],
        ];

        foreach ($places as $data) {
            $category = Category::where('slug', $data['category_slug'])->first();

            if (!$category)
                continue;

            $place = Place::create([
                'user_id' => $partner->id,
                'category_id' => $category->id,
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . uniqid(),
                'short_description' => $data['short_description'],
                'description' => $data['description'],
                'address' => $data['address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'difficulty' => $data['difficulty'],
                'duration' => $data['duration'],
                'best_season' => $data['best_season'],
                'status' => 'approved',
                'is_featured' => fake()->boolean(40),
            ]);

            // Agregar imágenes
            foreach ($data['images'] as $url) {
                $place->images()->create([
                    'image_path' => $url
                ]);
            }
        }
    }
}
