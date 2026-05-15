<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentProofUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_payment_proof()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'unpaid',
            'payment_method' => 'manual',
        ]);

        // Using create() with image mime type to avoid GD dependency
        $file = UploadedFile::fake()->create('payment_proof.jpg', 100, 'image/jpeg');

        $response = $this->postJson("/api/orders/{$order->id}/payment-proof", [
            'image' => $file,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Bukti transfer berhasil diunggah.',
            ]);

        $order->refresh();
        $this->assertEquals('pending_verification', $order->payment_status);
        $this->assertNotNull($order->payment_proof);
        Storage::disk('public')->assertExists($order->payment_proof);
    }

    public function test_upload_fails_if_not_image()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = Order::factory()->create(['user_id' => $user->id]);
        $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $response = $this->postJson("/api/orders/{$order->id}/payment-proof", [
            'image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_upload_fails_if_image_too_large()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = Order::factory()->create(['user_id' => $user->id]);
        $file = UploadedFile::fake()->create('large.jpg', 3000, 'image/jpeg'); // 3MB

        $response = $this->postJson("/api/orders/{$order->id}/payment-proof", [
            'image' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_user_cannot_upload_proof_for_others_order()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $order = Order::factory()->create(['user_id' => $otherUser->id]);
        $file = UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg');

        $response = $this->postJson("/api/orders/{$order->id}/payment-proof", [
            'image' => $file,
        ]);

        $response->assertStatus(404);
    }

    public function test_cannot_upload_if_already_paid_or_pending()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'payment_status' => 'paid',
        ]);

        $file = UploadedFile::fake()->create('proof.jpg', 100, 'image/jpeg');

        $response = $this->postJson("/api/orders/{$order->id}/payment-proof", [
            'image' => $file,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'status' => 'error',
                'message' => 'Pesanan ini sudah dikonfirmasi pembayarannya.',
            ]);
    }
}
