<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlaceSeeder extends Seeder
{
    public function run(): void
    {
        $data = json_decode(
            file_get_contents(storage_path('app/places.json')),
            true
        );

        // Convertir fechas ISO 8601 al formato MySQL (Y-m-d H:i:s)
        // y reasignar user_id fuera del rango [1,2,3] al socio (id 2)
        $data = array_map(function ($place) {
            if (!empty($place['created_at'])) {
                $place['created_at'] = date('Y-m-d H:i:s', strtotime($place['created_at']));
            }
            if (!empty($place['updated_at'])) {
                $place['updated_at'] = date('Y-m-d H:i:s', strtotime($place['updated_at']));
            }
            if (!in_array($place['user_id'], [1, 2, 3])) {
                $place['user_id'] = 2; // Reasignar al socio
            }
            return $place;
        }, $data);

        // Desactivar FK solo para truncar (necesario por la relación place_images → places)
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('place_images')->truncate();
        DB::table('places')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Insertar lugares con FK activas (user_ids ya son válidos: 1, 2 o 3)
        DB::table('places')->insert($data);

        // Insertar imágenes de los lugares
        $this->call(PlaceImageSeeder::class);
    }
}