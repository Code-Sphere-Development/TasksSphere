<?php

namespace Database\Factories;

use App\Models\ListItem;
use App\Models\TaskList;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ListItem>
 */
class ListItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_list_id' => TaskList::factory(),
            'title' => $this->faker->words(3, true),
            'note' => $this->faker->optional()->sentence(),
            'is_completed' => false,
            'position' => 0,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => ['is_completed' => true]);
    }
}
