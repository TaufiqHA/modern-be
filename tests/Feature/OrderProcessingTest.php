<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderProcessingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test successful order creation.
     */
    public function test_order_creation_success(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 10, 'price' => 100000]);

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'address_id' => $address->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 2,
                    ],
                ],
                'payment_method' => 'midtrans_va',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'order_id',
                'total_amount',
                'payment_url',
            ]);

        $this->assertEquals(200000, $response->json('total_amount'));

        $product->refresh();
        $this->assertEquals(8, $product->stock);
    }

    /**
     * Test stock exhaustion (Phase 5.1).
     */
    public function test_order_creation_fails_on_stock_exhaustion(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 5, 'price' => 100000]);

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'address_id' => $address->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 10, // More than stock
                    ],
                ],
                'payment_method' => 'midtrans_va',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test unauthorized address (Phase 5.2).
     */
    public function test_order_creation_fails_with_unauthorized_address(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $otherUser->id]);
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'address_id' => $address->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'payment_method' => 'midtrans_va',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Test data integrity for item snapshots (Phase 5.4).
     */
    public function test_order_items_store_price_snapshot(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 10, 'price' => 100000]);

        $this->actingAs($user)
            ->postJson('/api/orders', [
                'address_id' => $address->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'payment_method' => 'midtrans_va',
            ]);

        // Update product price
        $product->update(['price' => 200000]);

        // Check order item price is still the old one
        $this->assertDatabaseHas('order_items', [
            'product_id' => $product->id,
            'unit_price' => 100000,
        ]);
    }

    /**
     * Test pre-order type assignment.
     */
    public function test_order_type_is_pre_order_when_stock_is_zero(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 0, 'price' => 100000]);

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'address_id' => $address->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 1,
                    ],
                ],
                'payment_method' => 'midtrans_va',
            ]);

        $response->assertStatus(201);
        $orderId = $response->json('order_id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'type' => 'pre_order',
        ]);
    }
}
