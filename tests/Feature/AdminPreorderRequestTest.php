<?php

namespace Tests\Feature;

use App\Models\PreorderRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPreorderRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can view all preorder requests.
     */
    public function test_admin_can_view_all_preorder_requests(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user1 = User::factory()->create(['role' => 'user']);
        $user2 = User::factory()->create(['role' => 'user']);

        PreorderRequest::factory()->count(2)->create(['user_id' => $user1->id]);
        PreorderRequest::factory()->count(3)->create(['user_id' => $user2->id]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/preorder-requests');

        $response->assertStatus(200)
            ->assertJsonPath('data.total', 5);
    }

    /**
     * Test admin can update preorder request status.
     */
    public function test_admin_can_update_preorder_request_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $preorderRequest = PreorderRequest::factory()->create(['status' => 'pending']);

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/preorder-requests/{$preorderRequest->id}/status", [
                'status' => 'contacted',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pre-order request status updated successfully.',
            ])
            ->assertJsonPath('data.status', 'contacted');

        $this->assertDatabaseHas('preorder_requests', [
            'id' => $preorderRequest->id,
            'status' => 'contacted',
        ]);
    }

    /**
     * Test user cannot access admin preorder endpoints.
     */
    public function test_user_cannot_access_admin_preorder_endpoints(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)
            ->getJson('/api/admin/preorder-requests');

        $response->assertStatus(403);
    }
}
