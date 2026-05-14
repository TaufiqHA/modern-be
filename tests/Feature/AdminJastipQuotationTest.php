<?php

namespace Tests\Feature;

use App\Models\JastipRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminJastipQuotationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_submit_quotation()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $jastipRequest = JastipRequest::factory()->create([
            'status' => 'pending',
            'quote' => null,
        ]);

        $response = $this->patchJson("/api/admin/jastip/{$jastipRequest->id}/quote", [
            'price' => 2500000,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'id' => $jastipRequest->id,
                    'quote' => 2500000,
                    'status' => 'quotation',
                ],
            ]);

        $jastipRequest->refresh();
        $this->assertEquals(2500000, (int) $jastipRequest->quote);
        $this->assertEquals('quotation', $jastipRequest->status);
    }

    public function test_regular_user_cannot_submit_quotation()
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $jastipRequest = JastipRequest::factory()->create();

        $response = $this->patchJson("/api/admin/jastip/{$jastipRequest->id}/quote", [
            'price' => 1000000,
        ]);

        $response->assertStatus(403);
    }

    public function test_quotation_validation_fails_for_invalid_price()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $jastipRequest = JastipRequest::factory()->create();

        // Test non-numeric
        $response = $this->patchJson("/api/admin/jastip/{$jastipRequest->id}/quote", [
            'price' => 'invalid',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['price']);

        // Test negative
        $response = $this->patchJson("/api/admin/jastip/{$jastipRequest->id}/quote", [
            'price' => -100,
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['price']);
    }

    public function test_user_sees_updated_quotation_in_their_list()
    {
        $user = User::factory()->create();
        $jastipRequest = JastipRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'product_name' => 'Requested Item',
        ]);

        // Admin updates it
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $this->patchJson("/api/admin/jastip/{$jastipRequest->id}/quote", ['price' => 500000]);

        // User checks their list
        Sanctum::actingAs($user);
        $response = $this->getJson('/api/jastip/requests');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'id' => $jastipRequest->id,
                'product' => 'Requested Item',
                'status' => 'quotation',
                'quote' => 500000,
            ]);
    }
}
