<?php

namespace Tests\Feature;

use App\Models\JastipRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminDashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_dashboard_stats()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        // 1. Paid orders contribute to total_sales
        Order::factory()->create([
            'payment_status' => 'paid',
            'total_amount' => 100000,
            'status' => 'completed', // Avoid active_orders count
        ]);
        Order::factory()->create([
            'payment_status' => 'paid',
            'total_amount' => 50000,
            'status' => 'completed',
        ]);
        Order::factory()->create([
            'payment_status' => 'unpaid',
            'total_amount' => 200000,
            'status' => 'completed',
        ]);

        // 2. Active orders (pending, processed, shipped)
        Order::factory()->create(['status' => 'pending', 'payment_status' => 'unpaid']);
        Order::factory()->create(['status' => 'processed', 'payment_status' => 'unpaid']);
        Order::factory()->create(['status' => 'shipped', 'payment_status' => 'unpaid']);
        Order::factory()->create(['status' => 'completed', 'payment_status' => 'unpaid']);
        Order::factory()->create(['status' => 'cancelled', 'payment_status' => 'unpaid']);

        // 3. Pending Jastip requests
        JastipRequest::factory()->count(3)->create(['status' => 'pending']);
        JastipRequest::factory()->create(['status' => 'quotation']); // Should be ignored

        // 4. Low stock products (< 5)
        Product::factory()->create(['stock' => 3]);
        Product::factory()->create(['stock' => 0]);
        Product::factory()->create(['stock' => 5]); // Should be ignored (boundary check)
        Product::factory()->create(['stock' => 10]); // Should be ignored

        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertStatus(200)
            ->assertJson([
                'total_sales' => 150000,
                'active_orders' => 3,
                'pending_jastip' => 3,
                'low_stock' => 2,
            ]);
    }

    public function test_regular_user_cannot_view_dashboard_stats()
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/admin/dashboard/stats');

        $response->assertStatus(403);
    }
}
