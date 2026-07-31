<?php

namespace Database\Factories;

use App\Models\Task;
use App\Models\TaskCompletion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TaskCompletion>
 */
class TaskCompletionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'planned_at' => now(),
            'completed_at' => now(),
            'is_skipped' => false,
        ];
    }

    public function skipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_skipped' => true,
            'completed_at' => null,
        ]);
    }
}
