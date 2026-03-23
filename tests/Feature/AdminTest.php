<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Place;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
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

    public function test_admin_can_get_dashboard_stats(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/dashboard');

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'stats' => [
                         'total_users',
                         'total_places',
                         'pending_places',
                         'approved_places'
                     ]
                 ]);
    }

    public function test_non_admin_cannot_get_stats(): void
    {
        $response = $this->actingAs($this->user)->getJson('/api/admin/dashboard');

        $response->assertStatus(403);
    }

    public function test_admin_can_list_users(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/admin/users');

        $response->assertStatus(200);
    }

    public function test_admin_can_delete_user(): void
    {
        $targetUser = User::factory()->create();

        $response = $this->actingAs($this->admin)->deleteJson("/api/admin/users/{$targetUser->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', [
            'id' => $targetUser->id
        ]);
    }
}
