<?php

namespace Database\Factories;

use App\Models\TaskList;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskList>
 */
class TaskListFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'team_id' => null,
            'title' => $this->faker->words(2, true),
            'description' => $this->faker->optional()->sentence(),
            'type' => 'checklist',
            'icon' => null,
            'color' => $this->faker->optional()->hexColor(),
            'position' => 0,
        ];
    }

    public function checklist(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'checklist']);
    }

    public function tasks(): static
    {
        return $this->state(fn (array $attributes) => ['type' => 'tasks']);
    }
}
