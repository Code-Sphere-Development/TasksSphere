<?php

use App\Models\Task;
use App\Models\TaskCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function runCompletionBackfill(): void
{
    $migration = require database_path('migrations/2026_09_14_120000_backfill_completions_for_completed_tasks.php');
    $migration->up();
}

test('the backfill records a completion for a previously completed one off task', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'due_at' => '2026-01-23 08:00:00',
        'completed_at' => '2026-01-23 09:15:00',
    ]);

    runCompletionBackfill();

    $completion = $task->completions()->first();
    expect($completion)->not->toBeNull();
    expect($completion->planned_at->format('Y-m-d H:i:s'))->toBe('2026-01-23 08:00:00');
    expect($completion->completed_at->format('Y-m-d H:i:s'))->toBe('2026-01-23 09:15:00');
    expect($completion->is_skipped)->toBeFalse();
});

test('the backfill falls back to the completion time when no due date exists', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'due_at' => null,
        'completed_at' => '2026-01-23 09:15:00',
    ]);

    runCompletionBackfill();

    expect($task->completions()->first()->planned_at->format('Y-m-d H:i:s'))->toBe('2026-01-23 09:15:00');
});

test('the backfill can run twice without duplicating rows', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'due_at' => '2026-01-23 08:00:00',
        'completed_at' => '2026-01-23 09:15:00',
    ]);

    runCompletionBackfill();
    runCompletionBackfill();

    expect($task->completions()->count())->toBe(1);
});

test('the backfill ignores tasks that are not completed', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'due_at' => '2026-01-23 08:00:00',
        'completed_at' => null,
    ]);

    runCompletionBackfill();

    expect($task->completions()->count())->toBe(0);
});

test('the backfill leaves recurring tasks untouched', function () {
    $task = Task::factory()->recurring('daily', 1)->create([
        'due_at' => '2026-01-23 08:00:00',
        'completed_at' => '2026-01-23 09:15:00',
    ]);

    runCompletionBackfill();

    expect($task->completions()->count())->toBe(0);
});

test('the backfill does not touch a task that already has a completion', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'due_at' => '2026-01-23 08:00:00',
        'completed_at' => '2026-01-23 09:15:00',
    ]);
    TaskCompletion::create([
        'task_id' => $task->id,
        'planned_at' => '2026-01-23 08:00:00',
        'completed_at' => '2026-01-23 10:00:00',
        'is_skipped' => false,
    ]);

    runCompletionBackfill();

    expect($task->completions()->count())->toBe(1);
    expect($task->completions()->first()->completed_at->format('H:i'))->toBe('10:00');
});

test('the backfill skips soft deleted tasks', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'due_at' => '2026-01-23 08:00:00',
        'completed_at' => '2026-01-23 09:15:00',
    ]);
    $task->delete();

    runCompletionBackfill();

    expect($task->completions()->count())->toBe(0);
});
