<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codesphere_id' => fake()->unique()->numberBetween(1, 1_000_000_000),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
        ];
    }

    /**
     * Ein Altkonto aus der Zeit vor AccountSphere, noch nicht gekoppelt.
     */
    public function unlinked(): static
    {
        return $this->state(fn (array $attributes) => [
            'codesphere_id' => null,
        ]);
    }
}
