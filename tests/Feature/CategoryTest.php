<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->user = User::factory()->create(['role' => 'user']);
    }

    public function test_public_can_get_categories(): void
    {
        Category::create(['name' => 'Montaña', 'slug' => 'montana']);
        Category::create(['name' => 'Playa', 'slug' => 'playa']);

        $response = $this->getJson('/api/categories');

        $response->assertStatus(200)
                 ->assertJsonCount(2)
                 ->assertJsonFragment(['name' => 'Montaña'])
                 ->assertJsonFragment(['name' => 'Playa']);
    }

    public function test_admin_can_create_category(): void
    {
        // El middleware 'role:admin' protege esta ruta en routes/api.php
        $response = $this->actingAs($this->admin)->postJson('/api/categories', [
            'name' => 'Aventura'
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Aventura']);

        $this->assertDatabaseHas('categories', [
            'name' => 'Aventura',
            'slug' => 'aventura'
        ]);
    }

    public function test_user_cannot_create_category(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/categories', [
            'name' => 'Aventura'
        ]);

        // Dependiendo de la implementacion del middleware, puede ser 403 o 401
        $response->assertStatus(403);
    }

    public function test_admin_can_update_category(): void
    {
        $category = Category::create(['name' => 'Old Name', 'slug' => 'old-name']);

        $response = $this->actingAs($this->admin)->putJson("/api/categories/{$category->id}", [
            'name' => 'New Name'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Name',
            'slug' => 'new-name' // Str::slug se aplica en el controller
        ]);
    }

    public function test_admin_can_delete_category(): void
    {
        $category = Category::create(['name' => 'To Delete', 'slug' => 'to-delete']);

        $response = $this->actingAs($this->admin)->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('categories', [
            'id' => $category->id
        ]);
    }
}
