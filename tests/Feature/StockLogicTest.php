<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockLogicTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test availability_status accessor on Product model.
     */
    public function test_product_has_correct_availability_status(): void
    {
        $readyProduct = Product::factory()->create(['stock' => 10]);
        $preOrderProduct = Product::factory()->create(['stock' => 0]);

        $this->assertEquals('ready_stock', $readyProduct->availability_status);
        $this->assertEquals('pre_order', $preOrderProduct->availability_status);
    }

    /**
     * Test that order fails with 422 when buying more than stock (but stock > 0).
     */
    public function test_order_fails_when_stock_insufficient_but_greater_than_zero(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 5, 'price' => 100]);

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
     * Test that order type is ready_stock when stock is sufficient.
     */
    public function test_order_type_is_ready_stock_when_stock_sufficient(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 20, 'price' => 100]);

        $response = $this->actingAs($user)
            ->postJson('/api/orders', [
                'address_id' => $address->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity' => 5,
                    ],
                ],
                'payment_method' => 'midtrans_va',
            ]);

        $response->assertStatus(201);
        $orderId = $response->json('order_id');

        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'type' => 'ready_stock',
        ]);

        $product->refresh();

        $this->assertEquals(15, $product->stock);
        $this->assertEquals('ready_stock', $product->availability_status);
    }

    /**
     * Test that availability status changes when stock is updated manually (simulating Admin Update).
     */
    public function test_availability_status_changes_on_manual_stock_update(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $this->assertEquals('ready_stock', $product->availability_status);

        $product->update(['stock' => 0]);
        $this->assertEquals('pre_order', $product->availability_status);

        $product->update(['stock' => 5]);
        $this->assertEquals('ready_stock', $product->availability_status);
    }
}
