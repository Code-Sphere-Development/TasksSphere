<?php

use App\Livewire\TaskManager;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('it only shows completed tasks for the logged in user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Create tasks for both users
    $task1 = Task::factory()->create([
        'user_id' => $user1->id,
        'title' => 'User 1 Task',
        'recurrence_rule' => null,
        'is_archived' => false,
    ]);
    $task2 = Task::factory()->create([
        'user_id' => $user2->id,
        'title' => 'User 2 Task',
        'recurrence_rule' => null,
        'is_archived' => false,
    ]);

    // Mark tasks as completed
    TaskCompletion::create([
        'task_id' => $task1->id,
        'completed_at' => now(),
        'planned_at' => now(),
        'is_skipped' => false,
    ]);

    TaskCompletion::create([
        'task_id' => $task2->id,
        'completed_at' => now(),
        'planned_at' => now(),
        'is_skipped' => false,
    ]);

    // Test as user 1
    Livewire::actingAs($user1)
        ->test(TaskManager::class)
        ->assertViewHas('completedCompletions', function ($completions) use ($task1, $task2) {
            return $completions->contains('task_id', $task1->id) &&
                   ! $completions->contains('task_id', $task2->id);
        });

    // Test as user 2
    Livewire::actingAs($user2)
        ->test(TaskManager::class)
        ->assertViewHas('completedCompletions', function ($completions) use ($task1, $task2) {
            return $completions->contains('task_id', $task2->id) &&
                   ! $completions->contains('task_id', $task1->id);
        });
});
