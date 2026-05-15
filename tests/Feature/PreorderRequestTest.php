<?php

namespace Tests\Feature;

use App\Models\PreorderRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreorderRequestTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can submit a preorder request.
     */
    public function test_user_can_submit_preorder_request(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/preorder-requests', [
                'product_id' => $product->id,
                'notes' => 'Tolong kabari kalau size 42 ada.',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pre-order request submitted successfully.',
            ])
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'user_id',
                    'product_id',
                    'notes',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('preorder_requests', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'notes' => 'Tolong kabari kalau size 42 ada.',
            'status' => 'pending',
        ]);
    }

    /**
     * Test user can view their own preorder requests.
     */
    public function test_user_can_view_own_preorder_requests(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $otherUser = User::factory()->create(['role' => 'user']);

        $product = Product::factory()->create();

        PreorderRequest::factory()->count(3)->create(['user_id' => $user->id]);
        PreorderRequest::factory()->count(2)->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($user)
            ->getJson('/api/preorder-requests');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }
}
