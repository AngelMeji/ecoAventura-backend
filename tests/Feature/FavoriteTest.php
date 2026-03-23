<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Place $place;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        
        $category = Category::create([
            'name' => 'Montaña',
            'slug' => 'montana'
        ]);

        $this->place = Place::create([
            'user_id' => User::factory()->create()->id,
            'category_id' => $category->id,
            'name' => 'Lugar Hermoso',
            'slug' => 'lugar-hermoso-1',
            'short_description' => 'Short',
            'description' => 'This is a long description that meets the fifty character minimum requirement exactly.',
            'address' => 'Direccion',
            'latitude' => 10.0,
            'longitude' => -10.0,
            'status' => 'approved',
            'difficulty' => 'media',
            'duration' => '2 horas',
            'best_season' => 'Verano',
        ]);
    }

    public function test_user_can_add_favorite(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/favorites', [
            'place_id' => $this->place->id
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['message' => 'Agregado a favoritos']);

        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'place_id' => $this->place->id
        ]);
    }

    public function test_user_can_list_favorites(): void
    {
        // Add favorite directly to DB
        $this->user->favorites()->create([
            'place_id' => $this->place->id
        ]);

        $response = $this->actingAs($this->user, 'sanctum')->getJson('/api/favorites');

        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonFragment([
                     'id' => $this->place->id,
                     'name' => 'Lugar Hermoso'
                 ]);
    }

    public function test_user_can_remove_favorite(): void
    {
        $this->user->favorites()->create([
            'place_id' => $this->place->id
        ]);

        $response = $this->actingAs($this->user)->deleteJson("/api/favorites/{$this->place->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Eliminado de favoritos']);

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $this->user->id,
            'place_id' => $this->place->id
        ]);
    }
}
