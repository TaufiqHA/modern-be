<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockLog>
 */
class StockLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'qty_before' => $qtyBefore = $this->faker->numberBetween(10, 100),
            'qty_after' => $qtyAfter = $qtyBefore + $this->faker->numberBetween(-5, 20),
            'change_type' => $qtyAfter > $qtyBefore ? 'increment' : 'decrement',
            'reason' => $this->faker->randomElement(['restock', 'sale', 'manual adjustment']),
            'admin_id' => User::factory()->state(['role' => 'admin']),
        ];
    }
}
