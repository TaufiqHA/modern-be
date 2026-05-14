<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that order history requires authentication.
     */
    public function test_order_history_requires_authentication(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
    }

    /**
     * Test that order history returns an empty array for users with no orders.
     */
    public function test_order_history_returns_empty_array_for_new_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertExactJson([]);
    }

    /**
     * Test that order history returns correct data and ordering.
     */
    public function test_order_history_returns_correct_data_and_ordering(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        // Create an older order
        $oldOrder = Order::factory()->create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'total_amount' => 50000,
            'status' => 'completed',
            'created_at' => now()->subDays(2),
        ]);

        // Create a newer order
        $newOrder = Order::factory()->create([
            'user_id' => $user->id,
            'address_id' => $address->id,
            'total_amount' => 100000,
            'status' => 'pending',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonPath('0.id', $newOrder->id)
            ->assertJsonPath('0.total', 100000)
            ->assertJsonPath('1.id', $oldOrder->id)
            ->assertJsonPath('1.total', 50000);

        // Check ISO format
        $this->assertEquals($newOrder->created_at->toIso8601String(), $response->json('0.date'));
    }

    /**
     * Test that users can only see their own orders.
     */
    public function test_users_can_only_see_their_own_orders(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $address1 = Address::factory()->create(['user_id' => $user1->id]);
        $address2 = Address::factory()->create(['user_id' => $user2->id]);

        Order::factory()->create(['user_id' => $user1->id, 'address_id' => $address1->id]);
        Order::factory()->create(['user_id' => $user2->id, 'address_id' => $address2->id]);

        $response = $this->actingAs($user1)
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(1);
    }
}
