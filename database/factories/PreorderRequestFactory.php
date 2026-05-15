<?php

namespace Database\Factories;

use App\Models\PreorderRequest;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PreorderRequest>
 */
class PreorderRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory()->state(['is_preorder' => true]),
            'notes' => $this->faker->sentence(),
            'status' => $this->faker->randomElement(['pending', 'contacted', 'closed']),
        ];
    }
}
