<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can register.
     */
    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'EcoAvent1!*',
            'password_confirmation' => 'EcoAvent1!*',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'user' => ['id', 'name', 'email'],
                     'token'
                 ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    }

    /**
     * Test user can login.
     */
    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt($password = 'EcoAvent1!*'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user']);
    }

    /**
     * Test authenticated user can get profile.
     */
    public function test_user_can_get_profile(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
                         ->getJson('/api/me');

        $response->assertStatus(200)
                 ->assertJson([
                     'email' => $user->email,
                 ]);
    }
}
