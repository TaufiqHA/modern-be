<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test user can list their addresses.
     */
    public function test_user_can_list_addresses(): void
    {
        $user = User::factory()->create();
        Address::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/user/addresses');

        $response->assertStatus(200)
            ->assertJsonCount(2)
            ->assertJsonStructure([
                '*' => ['id', 'label', 'recipient', 'phone', 'detail', 'is_default'],
            ]);
    }

    /**
     * Test user can create an address.
     */
    public function test_user_can_create_address(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/addresses', [
                'label' => 'Rumah',
                'recipient' => 'John Doe',
                'phone' => '08123456789',
                'detail' => 'Jl. Minimalist No. 42, Jakarta',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'label' => 'Rumah',
                'recipient' => 'John Doe',
                'is_default' => true, // First address should be default
            ]);

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'label' => 'Rumah',
            'recipient_name' => 'John Doe',
        ]);
    }

    /**
     * Test first address is default, subsequent are not.
     */
    public function test_first_address_is_default_subsequent_are_not(): void
    {
        $user = User::factory()->create();

        // First address
        $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/addresses', [
                'label' => 'Alamat 1',
                'recipient' => 'User',
                'phone' => '123',
                'detail' => 'Detail 1',
            ]);

        // Second address
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/user/addresses', [
                'label' => 'Alamat 2',
                'recipient' => 'User',
                'phone' => '456',
                'detail' => 'Detail 2',
            ]);

        $response->assertStatus(201)
            ->assertJson(['is_default' => false]);
    }
}
