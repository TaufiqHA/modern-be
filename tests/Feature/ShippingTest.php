<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ShippingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test shipping cost calculation.
     */
    public function test_shipping_cost_calculation_success(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create([
            'user_id' => $user->id,
            'city_id' => '444', // Surabaya
        ]);

        Http::fake([
            '*' => Http::response([
                'rajaongkir' => [
                    'origin_details' => ['city_name' => 'Jakarta Barat'],
                    'destination_details' => ['city_name' => 'Surabaya'],
                    'results' => [
                        [
                            'costs' => [
                                [
                                    'service' => 'REG',
                                    'description' => 'Reguler',
                                    'cost' => [
                                        ['value' => 15000, 'etd' => '2-3'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/shipping/calculate', [
                'address_id' => $address->id,
                'weight' => 1000,
                'courier' => 'jne',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'origin' => 'Jakarta Barat',
                'destination' => 'Surabaya',
                'results' => [
                    [
                        'service' => 'REG',
                        'description' => 'Reguler',
                        'cost' => 15000,
                        'etd' => '2-3 Hari',
                    ],
                ],
            ]);
    }

    /**
     * Test shipping cost calculation fails when city_id is missing.
     */
    public function test_shipping_cost_calculation_fails_when_city_id_missing(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create([
            'user_id' => $user->id,
            'city_id' => null,
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/shipping/calculate', [
                'address_id' => $address->id,
                'weight' => 1000,
                'courier' => 'jne',
            ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Destination city ID is missing for the selected address.',
            ]);
    }

    /**
     * Test shipping cost calculation fails with invalid courier.
     */
    public function test_shipping_cost_calculation_fails_with_invalid_courier(): void
    {
        $user = User::factory()->create();
        $address = Address::factory()->create([
            'user_id' => $user->id,
            'city_id' => '444',
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/shipping/calculate', [
                'address_id' => $address->id,
                'weight' => 1000,
                'courier' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['courier']);
    }
}
