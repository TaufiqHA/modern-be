<?php

namespace Tests\Feature;

use App\Mail\OrderStatusUpdated;
use App\Models\Order;
use App\Models\User;
use App\Services\WhatsAppService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminOrderStatusUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_order_status_and_trigger_notifications()
    {
        Mail::fake();
        $this->mock(WhatsAppService::class, function ($mock) {
            $mock->shouldReceive('sendMessage')->once();
        });

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $user = User::factory()->create(['phone' => '08123456789']);
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->patchJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'shipped',
            'tracking_number' => 'RESI12345',
        ]);

        $response->assertStatus(200);

        Mail::assertSent(OrderStatusUpdated::class);
    }

    public function test_regular_user_cannot_update_order_status()
    {
        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);

        $order = Order::factory()->create();

        $response = $this->patchJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'shipped',
        ]);

        $response->assertStatus(403);
    }

    public function test_shipped_status_requires_tracking_number()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $order = Order::factory()->create(['status' => 'pending']);

        $response = $this->patchJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'shipped',
            // tracking_number missing
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tracking_number']);
    }

    public function test_validation_fails_for_invalid_status()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $order = Order::factory()->create();

        $response = $this->patchJson("/api/admin/orders/{$order->id}/status", [
            'status' => 'invalid_status',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }
}
