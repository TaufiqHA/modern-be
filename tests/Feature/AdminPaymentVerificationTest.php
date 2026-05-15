<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPaymentVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test admin can verify payment successfully.
     */
    public function test_admin_can_verify_payment_successfully(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create([
            'payment_status' => 'pending_verification',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/verify-payment");

        $response->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Pembayaran berhasil diverifikasi.');

        $order->refresh();
        $this->assertEquals('paid', $order->payment_status);
        $this->assertEquals('processed', $order->status);
        $this->assertNotNull($order->verified_at);
        $this->assertEquals($admin->id, $order->verified_by);
    }

    /**
     * Test regular user cannot verify payment.
     */
    public function test_regular_user_cannot_verify_payment(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $order = Order::factory()->create(['payment_status' => 'pending_verification']);

        $response = $this->actingAs($user)
            ->patchJson("/api/admin/orders/{$order->id}/verify-payment");

        $response->assertStatus(403);
    }

    /**
     * Test cannot verify payment that is already paid.
     */
    public function test_cannot_verify_payment_that_is_already_paid(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $order = Order::factory()->create(['payment_status' => 'paid']);

        $response = $this->actingAs($admin)
            ->patchJson("/api/admin/orders/{$order->id}/verify-payment");

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Pembayaran pesanan ini sudah diverifikasi sebelumnya.');
    }
}
