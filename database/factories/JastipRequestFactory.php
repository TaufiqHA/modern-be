<?php

namespace Database\Factories;

use App\Models\JastipRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JastipRequest>
 */
class JastipRequestFactory extends Factory
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
            'product_name' => $this->faker->words(3, true),
            'product_link' => $this->faker->url(),
            'image_url' => $this->faker->imageUrl(),
            'quantity' => $this->faker->numberBetween(1, 10),
            'notes' => $this->faker->sentence(),
            'status' => 'pending',
            'quote' => null,
        ];
    }
}
