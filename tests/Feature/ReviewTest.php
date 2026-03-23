<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Place;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;
    private Place $place;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['role' => 'admin']);
        
        $category = Category::create(['name' => 'Montaña', 'slug' => 'montana']);

        $this->place = Place::create([
            'user_id' => User::factory()->create()->id,
            'category_id' => $category->id,
            'name' => 'Lugar Review',
            'slug' => 'lugar-review-1',
            'short_description' => 'Short',
            'description' => 'Long enough description for fifty characters minimum.',
            'address' => 'Direccion',
            'latitude' => 10.0,
            'longitude' => -10.0,
            'status' => 'approved',
            'difficulty' => 'media',
            'duration' => '2 horas',
            'best_season' => 'Verano',
        ]);
    }

    public function test_user_can_post_review(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/places/{$this->place->id}/reviews", [
            'rating' => 4,
            'comment' => 'Excelente lugar'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->user->id,
            'place_id' => $this->place->id,
            'rating' => 4,
            'comment' => 'Excelente lugar'
        ]);
    }

    public function test_user_can_update_own_review(): void
    {
        $review = Review::create([
            'user_id' => $this->user->id,
            'place_id' => $this->place->id,
            'rating' => 3,
            'comment' => 'Mas o menos'
        ]);

        $response = $this->actingAs($this->user)->putJson("/api/reviews/{$review->id}", [
            'rating' => 5,
            'comment' => 'Mejoró mucho'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 5,
            'comment' => 'Mejoró mucho'
        ]);
    }

    public function test_admin_can_toggle_hide_review(): void
    {
        $review = Review::create([
            'user_id' => $this->user->id,
            'place_id' => $this->place->id,
            'rating' => 1,
            'comment' => 'Horrible sitio',
            'is_hidden' => false
        ]);

        $response = $this->actingAs($this->admin)->patchJson("/api/admin/reviews/{$review->id}/toggle-hide");

        $response->assertStatus(200);
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'is_hidden' => true
        ]);
    }
}
