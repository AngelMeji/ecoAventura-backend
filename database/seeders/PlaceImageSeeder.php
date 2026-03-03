<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlaceImageSeeder extends Seeder
{
    public function run(): void
    {
        $images = json_decode(
            file_get_contents(storage_path('app/place_images.json')),
            true
        );

        // Preparar los datos para inserción masiva
        $data = array_map(function ($image) {
            return [
                'id'         => (int) $image['id'],
                'place_id'   => (int) $image['place_id'],
                'image_path' => $image['image_path'],
                'is_primary' => (bool) $image['is_primary'],
                'created_at' => $image['created_at'],
                'updated_at' => $image['updated_at'],
            ];
        }, $images);

        DB::table('place_images')->insert($data);
    }
}