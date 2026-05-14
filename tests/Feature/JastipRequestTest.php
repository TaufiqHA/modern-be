<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\JastipRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JastipRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_jastip_request()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $file = UploadedFile::fake()->create('product.jpg', 100, 'image/jpeg');

        $response = $this->postJson('/api/jastip/request', [
            'product_name' => 'Limited Edition Sneakers',
            'product_link' => 'https://example.com/sneakers',
            'image' => $file,
            'quantity' => 2,
            'notes' => 'Size 42 please',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'request_id',
                'status',
                'message',
            ])
            ->assertJson([
                'status' => 'pending',
                'message' => 'Request submitted successfully',
            ]);

        $this->assertDatabaseHas('jastip_requests', [
            'user_id' => $user->id,
            'product_name' => 'Limited Edition Sneakers',
            'quantity' => 2,
            'status' => 'pending',
        ]);

        $requestId = $response->json('request_id');
        $this->assertStringStartsWith('JS-', $requestId);

        $jastipRequest = JastipRequest::find($requestId);
        $this->assertNotNull($jastipRequest->image_url);
        // Extract the path from the URL to check existence in storage
        $path = str_replace('/storage/', '', $jastipRequest->image_url);
        Storage::disk('public')->assertExists($path);
    }

    public function test_jastip_request_validation_fails()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/jastip/request', [
            'product_name' => '', // Required
            'product_link' => 'invalid-url', // Must be URL
            'quantity' => 0, // Must be at least 1
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_name', 'product_link', 'image', 'quantity']);
    }

    public function test_unauthenticated_user_cannot_submit_jastip_request()
    {
        $response = $this->postJson('/api/jastip/request', [
            'product_name' => 'Limited Edition Sneakers',
            'product_link' => 'https://example.com/sneakers',
            'quantity' => 2,
        ]);

        $response->assertStatus(401);
    }

    public function test_user_can_list_their_jastip_requests()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create requests with different timestamps and statuses
        $request1 = JastipRequest::factory()->create([
            'user_id' => $user->id,
            'product_name' => 'Old Request',
            'created_at' => now()->subDay(),
            'status' => 'pending',
            'quote' => null,
        ]);

        $request2 = JastipRequest::factory()->create([
            'user_id' => $user->id,
            'product_name' => 'New Request',
            'created_at' => now(),
            'status' => 'quotation',
            'quote' => 2500000,
        ]);

        // Request from another user
        JastipRequest::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->getJson('/api/jastip/requests');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJson([
                [
                    'id' => $request2->id,
                    'product' => 'New Request',
                    'status' => 'quotation',
                    'quote' => 2500000,
                ],
                [
                    'id' => $request1->id,
                    'product' => 'Old Request',
                    'status' => 'pending',
                    'quote' => null,
                ],
            ]);
    }

    public function test_new_user_gets_empty_list()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/jastip/requests');

        $response->assertStatus(200)
            ->assertJsonCount(0)
            ->assertJson([]);
    }

    public function test_user_can_convert_quoted_jastip_to_order()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->create(['user_id' => $user->id]);
        $jastipRequest = JastipRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'quotation',
            'quote' => 1000000,
            'quantity' => 2,
            'product_name' => 'Jastip Product',
        ]);

        $response = $this->postJson("/api/jastip/{$jastipRequest->id}/convert", [
            'address_id' => $address->id,
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['order_id', 'total_amount', 'message']);

        $orderId = $response->json('order_id');
        $this->assertDatabaseHas('orders', [
            'id' => $orderId,
            'user_id' => $user->id,
            'total_amount' => 2000000,
            'type' => 'jastip',
        ]);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $orderId,
            'product_name' => 'Jastip Product',
            'quantity' => 2,
            'unit_price' => 1000000,
            'subtotal' => 2000000,
        ]);

        $jastipRequest->refresh();
        $this->assertEquals('approved', $jastipRequest->status);
    }

    public function test_cannot_convert_if_not_quoted()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->create(['user_id' => $user->id]);
        $jastipRequest = JastipRequest::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'quote' => null,
        ]);

        $response = $this->postJson("/api/jastip/{$jastipRequest->id}/convert", [
            'address_id' => $address->id,
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'This request has not been quoted by admin yet or is already processed.',
            ]);
    }

    public function test_cannot_convert_others_jastip_request()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        Sanctum::actingAs($user);

        $address = Address::factory()->create(['user_id' => $user->id]);
        $jastipRequest = JastipRequest::factory()->create([
            'user_id' => $otherUser->id,
            'status' => 'quotation',
            'quote' => 1000000,
        ]);

        $response = $this->postJson("/api/jastip/{$jastipRequest->id}/convert", [
            'address_id' => $address->id,
            'payment_method' => 'bank_transfer',
        ]);

        $response->assertStatus(404);
    }
}
