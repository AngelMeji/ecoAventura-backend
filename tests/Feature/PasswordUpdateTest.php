<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use DatabaseTransactions;

    public function test_user_can_update_password_with_valid_data()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->putJson('/api/me/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => '¡Éxito! Tu contraseña ha sido actualizada correctamente.',
            ]);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_current_password_must_be_correct()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->putJson('/api/me/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_new_password_must_be_confirmed()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->putJson('/api/me/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'mismatch',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_new_password_min_length()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->putJson('/api/me/password', [
            'current_password' => 'password123',
            'password' => '12345',
            'password_confirmation' => '12345',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }
}
