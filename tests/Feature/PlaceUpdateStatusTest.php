<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PlaceUpdateStatusTest extends TestCase
{
    use DatabaseTransactions;

    public function test_partner_update_forces_pending_status()
    {
        // Crear usuario socio
        $user = User::factory()->create(['role' => 'partner']);
        
        // Crear categoría manualmente
        $category = Category::create(['name' => 'Test Category ' . uniqid(), 'slug' => 'test-cat-' . uniqid()]);

        // Crear lugar aprobado
        $place = Place::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Lugar Original',
            'slug' => 'lugar-original',
            'short_description' => 'Descripción corta',
            'description' => 'Descripción larga de más de 50 caracteres para validación',
            'address' => 'Dirección',
            'latitude' => 10.0,
            'longitude' => -75.0,
            'status' => 'approved', // Inicialmente aprobado
            'difficulty' => 'media',
            'duration' => '2 horas',
            'best_season' => 'Verano',
        ]);

        // Simular actualización por el socio
        $response = $this->actingAs($user)->putJson("/api/places/{$place->id}", [
            'name' => 'Lugar Actualizado',
        ]);

        $response->assertStatus(200);

        // Verificar que el estado cambió a pending
        $this->assertEquals('pending', $place->fresh()->status);
        $this->assertEquals('Lugar Actualizado', $place->fresh()->name);
    }

    public function test_admin_update_preserves_status()
    {
        // Crear usuario admin
        $admin = User::factory()->create(['role' => 'admin']);
        
        // Crear usuario socio (dueño)
        $user = User::factory()->create(['role' => 'partner']);
        
        // Crear categoría manualmente
        $category = Category::create(['name' => 'Test Category ' . uniqid(), 'slug' => 'test-cat-' . uniqid()]);

        // Crear lugar aprobado
        $place = Place::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Lugar Original',
            'slug' => 'lugar-original-admin',
            'short_description' => 'Descripción corta',
            'description' => 'Descripción larga de más de 50 caracteres para validación',
            'address' => 'Dirección',
            'latitude' => 10.0,
            'longitude' => -75.0,
            'status' => 'approved',
            'difficulty' => 'media',
            'duration' => '2 horas',
            'best_season' => 'Verano',
        ]);

        // Simular actualización por admin (sin enviar status)
        $response = $this->actingAs($admin)->putJson("/api/places/{$place->id}", [
            'name' => 'Lugar Actualizado por Admin',
        ]);

        $response->assertStatus(200);

        // Verificar que el estado SE MANTIENE approved
        $this->assertEquals('approved', $place->fresh()->status);
        $this->assertEquals('Lugar Actualizado por Admin', $place->fresh()->name);
    }

    public function test_admin_can_change_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'partner']);
        // Crear categoría manualmente
        $category = Category::create(['name' => 'Test Category ' . uniqid(), 'slug' => 'test-cat-' . uniqid()]);
        
        $place = Place::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Lugar Pendiente',
            'slug' => 'lugar-pendiente',
            'short_description' => 'Desc',
            'description' => 'Desc larga.......',
            'address' => 'Dir',
            'latitude' => 0, 'longitude' => 0,
            'status' => 'pending',
            'difficulty' => 'baja', 'duration' => '1h', 'best_season' => 'todo',
        ]);

        // Admin aprueba
        $response = $this->actingAs($admin)->putJson("/api/places/{$place->id}", [
            'status' => 'approved',
        ]);

        $response->assertStatus(200);
        $this->assertEquals('approved', $place->fresh()->status);
    }

    public function test_partner_can_set_own_place_pending()
    {
        $user = User::factory()->create(['role' => 'partner']);
        // Crear categoría manualmente
        $category = Category::create(['name' => 'Test Category ' . uniqid(), 'slug' => 'test-cat-' . uniqid()]);
        
        $place = Place::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'name' => 'Lugar Aprobado',
            'slug' => 'lugar-aprobado-' . uniqid(),
            'short_description' => 'Desc',
            'description' => 'Desc larga.......',
            'address' => 'Dir',
            'latitude' => 0, 'longitude' => 0,
            'status' => 'approved',
            'difficulty' => 'baja', 'duration' => '1h', 'best_season' => 'todo',
        ]);

        // El dueño llama a set-pending
        $response = $this->actingAs($user)->patchJson("/api/places/{$place->id}/set-pending");

        $response->assertStatus(200);
        $this->assertEquals('pending', $place->fresh()->status);
    }
}
