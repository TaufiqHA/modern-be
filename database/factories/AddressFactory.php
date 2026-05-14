<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
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
            'label' => $this->faker->randomElement(['Rumah', 'Kantor']),
            'recipient_name' => $this->faker->name,
            'phone_number' => $this->faker->phoneNumber,
            'full_address' => $this->faker->address,
            'is_default' => false,
        ];
    }
}
