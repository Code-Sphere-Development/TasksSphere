<?php

use App\Enums\TaskRotation;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function rotatingTask(array $people, TaskRotation $strategy): Task
{
    $owner = $people[0];
    $task = Task::factory()->for($owner)->create([
        'due_at' => '2026-01-23 08:00:00',
        'recurrence_rule' => ['frequency' => 'daily', 'interval' => 1],
        'recurrence_timezone' => null,
        'rotation_strategy' => $strategy,
    ]);

    foreach ($people as $person) {
        $task->assignees()->attach($person->id, ['assigned_by' => $owner->id]);
    }

    return $task->fresh();
}

test('without a strategy the responsible person stays', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $task = rotatingTask([$a, $b], TaskRotation::KeepLast);
    $task->update(['rotation_strategy' => null, 'assigned_to' => $a->id]);

    $task->complete();

    expect($task->fresh()->assigned_to)->toBe($a->id);
});

test('keep last leaves the responsible person untouched', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $task = rotatingTask([$a, $b], TaskRotation::KeepLast);
    $task->update(['assigned_to' => $b->id]);

    $task->complete();

    expect($task->fresh()->assigned_to)->toBe($b->id);
});

test('random picks someone from the pool', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $task = rotatingTask([$a, $b], TaskRotation::Random);

    $task->complete();

    expect([$a->id, $b->id])->toContain($task->fresh()->assigned_to);
});

test('least completed picks the person with the fewest completions', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $task = rotatingTask([$a, $b], TaskRotation::LeastCompleted);

    // a hat schon zweimal erledigt, b noch nie.
    TaskCompletion::create(['task_id' => $task->id, 'completed_by' => $a->id, 'planned_at' => '2026-01-21 08:00:00', 'completed_at' => now(), 'is_skipped' => false]);
    TaskCompletion::create(['task_id' => $task->id, 'completed_by' => $a->id, 'planned_at' => '2026-01-22 08:00:00', 'completed_at' => now(), 'is_skipped' => false]);

    $task->complete(null, $a);

    expect($task->fresh()->assigned_to)->toBe($b->id);
});

test('least assigned picks the person responsible for the fewest tasks', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $task = rotatingTask([$a, $b], TaskRotation::LeastAssigned);

    // a ist bereits fuer drei andere Aufgaben zustaendig.
    Task::factory()->count(3)->for($a)->create(['assigned_to' => $a->id]);

    $task->complete();

    expect($task->fresh()->assigned_to)->toBe($b->id);
});

test('rotation does not apply to a one off task', function () {
    $a = User::factory()->create();
    $b = User::factory()->create();
    $task = rotatingTask([$a, $b], TaskRotation::LeastCompleted);
    $task->update(['recurrence_rule' => null, 'assigned_to' => $a->id]);

    $task->complete();

    expect($task->fresh()->assigned_to)->toBe($a->id);
});

test('rotation with an empty pool leaves the assignment alone', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create([
        'due_at' => '2026-01-23 08:00:00',
        'recurrence_rule' => ['frequency' => 'daily', 'interval' => 1],
        'recurrence_timezone' => null,
        'rotation_strategy' => TaskRotation::Random,
        'assigned_to' => $owner->id,
    ]);

    $task->complete();

    expect($task->fresh()->assigned_to)->toBe($owner->id);
});

test('every strategy has a label', function () {
    app()->setLocale('de');

    expect(TaskRotation::Random->label())->toBe('Zufällig');
    expect(TaskRotation::LeastAssigned->label())->toBe('Am seltensten zuständig');
    expect(TaskRotation::LeastCompleted->label())->toBe('Am seltensten erledigt');
    expect(TaskRotation::KeepLast->label())->toBe('Zuständige Person bleibt');
});
