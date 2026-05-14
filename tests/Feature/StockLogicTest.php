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
     * Test that order type becomes pre_order when buying more than stock.
     */
    public function test_order_type_becomes_pre_order_when_stock_insufficient(): void
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
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('order.type', 'pre_order');

        $product->refresh();
        $this->assertEquals(-5, $product->stock); // Decremented even if insufficient
        $this->assertEquals('pre_order', $product->availability_status);
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
            ]);

        $response->assertStatus(201);
        $response->assertJsonPath('order.type', 'ready_stock');

        $product->refresh();
        $this->assertEquals(15, $product->stock);
        $this->assertEquals('ready_stock', $product->availability_status);
    }
}
