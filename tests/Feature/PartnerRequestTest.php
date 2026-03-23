<?php

namespace Tests\Feature;

use App\Models\PartnerRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartnerRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'user']);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_user_can_request_partner_status(): void
    {
        $response = $this->actingAs($this->user)->postJson('/api/partner-requests', [
            'business_name' => 'Agencia de Viajes',
            'contact_phone' => '+1234567890',
            'description' => 'Tenemos experiencia realizando tours.',
            'place_name' => 'El Mirador',
            'place_address' => 'Avenida Principal'
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('partner_requests', [
            'user_id' => $this->user->id,
            'status' => 'pending'
        ]);
    }

    public function test_admin_can_approve_partner(): void
    {
        $request = PartnerRequest::create([
            'user_id' => $this->user->id,
            'business_name' => 'Agencia',
            'contact_phone' => '123',
            'description' => 'Desc',
            'place_name' => 'Mirador',
            'place_address' => 'Calle 1',
            'status' => 'pending'
        ]);

        $category = new \App\Models\Category([
            'name' => 'Default Category',
            'slug' => 'default-category'
        ]);
        $category->id = 1;
        $category->save();

        $response = $this->actingAs($this->admin)->patchJson("/api/admin/partner-requests/{$request->id}/approve");

        $response->assertStatus(200);
        
        // Assert the user got promoted to partner
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'role' => 'partner'
        ]);

        // Assert request is approved
        $this->assertDatabaseHas('partner_requests', [
            'id' => $request->id,
            'status' => 'approved'
        ]);
    }
}
