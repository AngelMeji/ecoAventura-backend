<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlaceTest extends TestCase
{
    use RefreshDatabase;

    private User $partner;
    private User $admin;
    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->partner = User::factory()->create(['role' => 'partner']);
        $this->admin = User::factory()->create(['role' => 'admin']);

        // Create a category for testing
        $this->category = Category::create([
            'name' => 'Cascadas',
            'slug' => 'cascadas',
            'description' => 'Hermosas cascadas',
            'icon' => 'FaWater'
        ]);
    }

    /**
     * Test public can get approved places
     */
    public function test_public_can_get_approved_places(): void
    {
        // An approved place
        Place::create([
            'user_id' => $this->partner->id,
            'category_id' => $this->category->id,
            'name' => 'Cascada Aprobada',
            'slug' => 'cascada-aprobada-123',
            'short_description' => 'Short',
            'description' => 'A very long description because it requires at least 50 characters to pass validation.',
            'address' => 'Direccion',
            'latitude' => 10.0,
            'longitude' => -10.0,
            'status' => 'approved',
            'difficulty' => 'media',
            'duration' => '2 horas',
            'best_season' => 'Verano',
        ]);

        // A pending place (should not be visible)
        Place::create([
            'user_id' => $this->partner->id,
            'category_id' => $this->category->id,
            'name' => 'Cascada Pendiente',
            'slug' => 'cascada-pendiente-123',
            'short_description' => 'Short',
            'description' => 'This is another long description to satisfy the length requirement of fifty chars.',
            'address' => 'Direccion',
            'latitude' => 10.0,
            'longitude' => -10.0,
            'status' => 'pending',
            'difficulty' => 'media',
            'duration' => '2 horas',
            'best_season' => 'Verano',
        ]);

        $response = $this->getJson('/api/places');

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Cascada Aprobada'])
                 ->assertJsonMissing(['name' => 'Cascada Pendiente']);
    }

    /**
     * Test partner can create a place (creates as pending)
     */
    public function test_partner_can_create_place(): void
    {
        Storage::fake('public');
        
        $image = UploadedFile::fake()->image('test.jpg', 600, 600);

        $response = $this->actingAs($this->partner)->postJson('/api/places', [
            'name' => 'Nuevo Lugar',
            'category_id' => $this->category->id,
            'short_description' => 'Breve desc',
            'description' => 'Esta es una descripcion lo suficientemente larga para superar los cincuenta caracteres minimos.',
            'address' => 'Calle 123',
            'latitude' => 15.5,
            'longitude' => -80.0,
            'difficulty' => 'media',
            'duration' => '3 horas',
            'best_season' => 'Invierno',
            'images' => [$image],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('places', [
            'name' => 'Nuevo Lugar',
            'status' => 'pending', // Partner creations are pending
        ]);

        // Image should have been processed and saved as webp
        // Check if there are any webp files in the places folder
        $files = Storage::disk('public')->files('places');
        $this->assertNotEmpty($files);
        $this->assertStringContainsString('.webp', $files[0]);
    }

    /**
     * Test admin creates an approved place directly
     */
    public function test_admin_creates_approved_place(): void
    {
        Storage::fake('public');
        
        $image = UploadedFile::fake()->image('test.jpg', 600, 600);

        $response = $this->actingAs($this->admin)->postJson('/api/places', [
            'name' => 'Lugar Admin',
            'category_id' => $this->category->id,
            'short_description' => 'Breve desc',
            'description' => 'Esta es una descripcion lo suficientemente larga para superar los cincuenta caracteres minimos.',
            'address' => 'Calle 123',
            'latitude' => 15.5,
            'longitude' => -80.0,
            'difficulty' => 'alta',
            'duration' => '3 horas',
            'best_season' => 'Invierno',
            'images' => [$image],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('places', [
            'name' => 'Lugar Admin',
            'status' => 'approved', // Admin creations are immediately approved
        ]);
    }

    /**
     * Test admin can approve a pending place
     */
    public function test_admin_can_approve_place(): void
    {
        $place = Place::create([
            'user_id' => $this->partner->id,
            'category_id' => $this->category->id,
            'name' => 'Cascada Pendiente',
            'slug' => 'cascada-pendiente-123',
            'short_description' => 'Short',
            'description' => 'This is another long description to satisfy the length requirement of fifty chars.',
            'address' => 'Direccion',
            'latitude' => 10.0,
            'longitude' => -10.0,
            'status' => 'pending',
            'difficulty' => 'media',
            'duration' => '2 horas',
            'best_season' => 'Verano',
        ]);

        // The route is PATCH /places/{id}/approve (authenticated with sanctum)
        $response = $this->actingAs($this->admin)->patchJson("/api/places/{$place->id}/approve");

        $response->assertStatus(200);
        $this->assertDatabaseHas('places', [
            'id' => $place->id,
            'status' => 'approved',
        ]);
    }
}
