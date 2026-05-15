<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => 'ORD-'.now()->format('Ymd').'-'.Str::upper(Str::random(4)),
            'user_id' => User::factory(),
            'address_id' => Address::factory(),
            'total_amount' => $this->faker->numberBetween(100000, 2000000),
            'shipping_cost' => $this->faker->numberBetween(10000, 50000),
            'courier_service' => $this->faker->randomElement(['JNE - OKE', 'JNT - REG', 'SICEPAT - REG']),
            'type' => $this->faker->randomElement(['ready_stock', 'pre_order', 'jastip']),
            'status' => $this->faker->randomElement(['pending', 'processed', 'shipped', 'completed']),
            'payment_status' => $this->faker->randomElement(['unpaid', 'paid', 'refunded']),
            'payment_method' => $this->faker->randomElement(['midtrans_va', 'go_pay', 'bank_transfer']),
            'verified_at' => null,
            'verified_by' => null,
        ];
    }
}
