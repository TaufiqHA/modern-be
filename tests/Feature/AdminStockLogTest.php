<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStockLogTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can view stock logs for a product.
     */
    public function test_admin_can_view_stock_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['name' => 'Nike Dunk Low', 'stock' => 15]);

        StockLog::factory()->create([
            'product_id' => $product->id,
            'qty_before' => 10,
            'qty_after' => 15,
            'change_type' => 'increment',
            'reason' => 'Restock from vendor',
            'admin_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/products/{$product->id}/stock-logs");

        $response->assertStatus(200)
            ->assertJsonPath('product_name', 'Nike Dunk Low')
            ->assertJsonPath('current_stock', 15)
            ->assertJsonCount(1, 'logs')
            ->assertJsonPath('logs.0.adjustment', 5)
            ->assertJsonPath('logs.0.admin_name', $admin->name);
    }

    /**
     * Test view stock logs returns 404 for non-existent product.
     */
    public function test_view_stock_logs_returns_404_for_non_existent_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/products/999/stock-logs');

        $response->assertStatus(404);
    }

    /**
     * Test regular user cannot view stock logs.
     */
    public function test_regular_user_cannot_view_stock_logs(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $product = Product::factory()->create();

        $response = $this->actingAs($user)
            ->getJson("/api/admin/products/{$product->id}/stock-logs");

        $response->assertStatus(403);
    }
}
