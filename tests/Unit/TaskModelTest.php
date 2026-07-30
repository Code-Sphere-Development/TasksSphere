<?php

use App\Models\Task;
use App\Models\TaskCompletion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// isRecurring
// ---------------------------------------------------------------------------

test('isRecurring is true when a recurrence rule is present', function () {
    $task = Task::factory()->recurring('daily', 1)->create();

    expect($task->isRecurring())->toBeTrue();
});

test('isRecurring is false when there is no recurrence rule', function () {
    $task = Task::factory()->create(['recurrence_rule' => null]);

    expect($task->isRecurring())->toBeFalse();
});

test('isRecurring is false for an empty recurrence rule array', function () {
    $task = new Task;
    $task->recurrence_rule = [];

    expect($task->isRecurring())->toBeFalse();
});

// ---------------------------------------------------------------------------
// isHandledAt / isSkippedAt
// ---------------------------------------------------------------------------

test('isHandledAt detects a matching completion and ignores others', function () {
    $task = Task::factory()->recurring('daily', 1)->create([
        'due_at' => '2026-01-23 08:00:00',
        'recurrence_timezone' => null,
    ]);

    TaskCompletion::factory()->create([
        'task_id' => $task->id,
        'planned_at' => '2026-01-23 08:00:00',
    ]);

    $task = $task->fresh();

    expect($task->isHandledAt('2026-01-23 08:00:00'))->toBeTrue();
    expect($task->isHandledAt('2026-01-24 08:00:00'))->toBeFalse();
});

test('isSkippedAt is true only for skipped completions', function () {
    $task = Task::factory()->recurring('daily', 1)->create([
        'recurrence_timezone' => null,
    ]);

    TaskCompletion::factory()->skipped()->create([
        'task_id' => $task->id,
        'planned_at' => '2026-01-23 08:00:00',
    ]);

    TaskCompletion::factory()->create([
        'task_id' => $task->id,
        'planned_at' => '2026-01-24 08:00:00',
    ]);

    $task = $task->fresh();

    expect($task->isSkippedAt('2026-01-23 08:00:00'))->toBeTrue();
    expect($task->isHandledAt('2026-01-23 08:00:00'))->toBeTrue();

    // A regular (non skipped) completion is handled but not skipped.
    expect($task->isSkippedAt('2026-01-24 08:00:00'))->toBeFalse();
    expect($task->isHandledAt('2026-01-24 08:00:00'))->toBeTrue();
});

test('isHandledAt honours the recurrence timezone', function () {
    // 08:00 in Berlin (winter, UTC+1) is 07:00 UTC.
    $task = Task::factory()->recurring('daily', 1)->create([
        'recurrence_timezone' => 'Europe/Berlin',
    ]);

    TaskCompletion::factory()->create([
        'task_id' => $task->id,
        'planned_at' => '2026-01-23 07:00:00', // stored as UTC
    ]);

    $task = $task->fresh();

    expect($task->isHandledAt('2026-01-23 08:00:00'))->toBeTrue();
});

// ---------------------------------------------------------------------------
// complete()
// ---------------------------------------------------------------------------

test('complete on a non recurring task sets completed_at and persists', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'due_at' => '2026-01-23 08:00:00',
        'completed_at' => null,
    ]);

    $task->complete();

    expect($task->completed_at)->not->toBeNull();
    expect($task->fresh()->completed_at)->not->toBeNull();
    // Non recurring tasks never create completion rows.
    expect($task->completions()->count())->toBe(0);
});

test('complete on a recurring task records a completion and advances due_at', function () {
    $task = Task::factory()->recurring('daily', 1)->create([
        'due_at' => '2026-01-23 08:00:00',
        'recurrence_timezone' => null,
    ]);

    $task->complete();

    $completion = $task->completions()->first();
    expect($completion)->not->toBeNull();
    expect($completion->planned_at->format('Y-m-d H:i:s'))->toBe('2026-01-23 08:00:00');
    expect($completion->completed_at)->not->toBeNull();
    expect($completion->is_skipped)->toBeFalse();

    // due_at moved to the next day at the same time.
    expect($task->fresh()->due_at->format('Y-m-d H:i:s'))->toBe('2026-01-24 08:00:00');
});

test('complete on a recurring task accepts an explicit planned date', function () {
    $task = Task::factory()->recurring('daily', 1)->create([
        'due_at' => '2026-01-23 08:00:00',
        'recurrence_timezone' => null,
    ]);

    $task->complete('2026-01-25 08:00:00');

    $completion = $task->completions()->first();
    expect($completion->planned_at->format('Y-m-d H:i:s'))->toBe('2026-01-25 08:00:00');
    // due_at is recalculated from the provided planned date.
    expect($task->fresh()->due_at->format('Y-m-d H:i:s'))->toBe('2026-01-26 08:00:00');
});

// ---------------------------------------------------------------------------
// skip()
// ---------------------------------------------------------------------------

test('skip on a recurring task records a skipped completion and advances due_at', function () {
    $task = Task::factory()->recurring('daily', 1)->create([
        'due_at' => '2026-01-23 08:00:00',
        'recurrence_timezone' => null,
    ]);

    $task->skip('2026-01-23 08:00:00');

    $completion = $task->completions()->first();
    expect($completion)->not->toBeNull();
    expect($completion->is_skipped)->toBeTrue();
    expect($completion->completed_at)->toBeNull();

    // Skipping the current occurrence advances due_at.
    expect($task->fresh()->due_at->format('Y-m-d H:i:s'))->toBe('2026-01-24 08:00:00');
});

test('skip on a non current occurrence does not move due_at', function () {
    $task = Task::factory()->recurring('daily', 1)->create([
        'due_at' => '2026-01-23 08:00:00',
        'recurrence_timezone' => null,
    ]);

    $task->skip('2026-01-25 08:00:00');

    expect($task->completions()->where('is_skipped', true)->count())->toBe(1);
    // due_at unchanged because the skipped date is not the current due_at.
    expect($task->fresh()->due_at->format('Y-m-d H:i:s'))->toBe('2026-01-23 08:00:00');
});

test('skip on a non recurring task does nothing', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'due_at' => '2026-01-23 08:00:00',
    ]);

    $task->skip('2026-01-23 08:00:00');

    expect($task->completions()->count())->toBe(0);
    expect($task->fresh()->due_at->format('Y-m-d H:i:s'))->toBe('2026-01-23 08:00:00');
});

// ---------------------------------------------------------------------------
// getOccurrences (non recurring)
// ---------------------------------------------------------------------------

test('getOccurrences returns a single dateless occurrence for a task without due date', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'due_at' => null,
    ]);

    $occurrences = $task->getOccurrences('2026-01-23 00:00:00', '2026-01-26 00:00:00');

    expect($occurrences)->toHaveCount(1);
    expect($occurrences->first()['planned_at'])->toBeNull();
    expect($occurrences->first()['is_completed'])->toBeFalse();
});

test('getOccurrences returns an overdue non recurring task that is not completed', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'due_at' => '2026-01-20 08:00:00',
        'completed_at' => null,
    ]);

    $occurrences = $task->getOccurrences('2026-01-23 00:00:00', '2026-01-26 00:00:00');

    expect($occurrences)->toHaveCount(1);
    expect($occurrences->first()['planned_at']->format('Y-m-d H:i:s'))->toBe('2026-01-20 08:00:00');
    expect($occurrences->first()['is_completed'])->toBeFalse();
});

test('getOccurrences hides an overdue non recurring task that is already completed', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'due_at' => '2026-01-20 08:00:00',
        'completed_at' => '2026-01-20 09:00:00',
    ]);

    $occurrences = $task->getOccurrences('2026-01-23 00:00:00', '2026-01-26 00:00:00');

    expect($occurrences)->toHaveCount(0);
});

test('getOccurrences returns a future non recurring task reflecting completion state', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'due_at' => '2026-01-24 08:00:00',
        'completed_at' => null,
    ]);

    $occurrences = $task->getOccurrences('2026-01-23 00:00:00', '2026-01-26 00:00:00');

    expect($occurrences)->toHaveCount(1);
    expect($occurrences->first()['planned_at']->format('Y-m-d H:i:s'))->toBe('2026-01-24 08:00:00');
    expect($occurrences->first()['is_completed'])->toBeFalse();

    $task->completed_at = Carbon::parse('2026-01-24 09:00:00');
    $task->save();

    $occurrences = $task->fresh()->getOccurrences('2026-01-23 00:00:00', '2026-01-26 00:00:00');
    expect($occurrences->first()['is_completed'])->toBeTrue();
});

// ---------------------------------------------------------------------------
// getOccurrences (recurring)
// ---------------------------------------------------------------------------

test('getOccurrences expands a recurring task within the window', function () {
    $task = Task::factory()->recurring('daily', 1)->create([
        'due_at' => '2026-01-23 08:00:00',
        'recurrence_timezone' => null,
    ]);

    $occurrences = $task->getOccurrences('2026-01-23 00:00:00', '2026-01-26 00:00:00');

    expect($occurrences)->toHaveCount(3);
    expect($occurrences->pluck('planned_at')->map->format('Y-m-d H:i:s')->all())->toBe([
        '2026-01-23 08:00:00',
        '2026-01-24 08:00:00',
        '2026-01-25 08:00:00',
    ]);
    expect($occurrences->every(fn ($o) => $o['is_completed'] === false))->toBeTrue();
});

test('getOccurrences marks a handled recurring occurrence as completed', function () {
    $task = Task::factory()->recurring('daily', 1)->create([
        'due_at' => '2026-01-23 08:00:00',
        'recurrence_timezone' => null,
    ]);

    TaskCompletion::factory()->create([
        'task_id' => $task->id,
        'planned_at' => '2026-01-24 08:00:00',
    ]);

    $task = $task->fresh();
    $occurrences = $task->getOccurrences('2026-01-23 00:00:00', '2026-01-26 00:00:00');

    $handled = $occurrences->firstWhere(
        fn ($o) => $o['planned_at']->format('Y-m-d H:i:s') === '2026-01-24 08:00:00'
    );
    expect($handled['is_completed'])->toBeTrue();
});

test('getOccurrences includes an overdue recurring occurrence before the window', function () {
    $task = Task::factory()->recurring('daily', 1)->create([
        'due_at' => '2026-01-20 08:00:00',
        'recurrence_timezone' => null,
    ]);

    $occurrences = $task->getOccurrences('2026-01-23 00:00:00', '2026-01-23 12:00:00');

    expect($occurrences->first()['planned_at']->format('Y-m-d H:i:s'))->toBe('2026-01-20 08:00:00');
    expect($occurrences->first()['is_completed'])->toBeFalse();
    // The overdue occurrence plus the ones that fall inside the window.
    expect($occurrences->count())->toBeGreaterThan(1);
});

test('getOccurrences derives a start date for a recurring task without due_at', function () {
    $task = Task::factory()->recurring('daily', 1)->create([
        'due_at' => null,
        'recurrence_timezone' => null,
    ]);

    $start = now()->format('Y-m-d H:i:s');
    $end = now()->addDays(3)->format('Y-m-d H:i:s');

    $occurrences = $task->getOccurrences($start, $end);

    expect($occurrences)->not->toBeEmpty();
});

// ---------------------------------------------------------------------------
// calculateNextDueDate — addInterval branches via the "times" path
// ---------------------------------------------------------------------------

test('calculateNextDueDate returns null when the task is not recurring', function () {
    $task = new Task;
    $task->due_at = Carbon::parse('2026-01-23 08:00:00');

    expect($task->calculateNextDueDate())->toBeNull();
});

test('calculateNextDueDate uses the daily default for an unknown frequency', function () {
    $task = new Task;
    $task->due_at = Carbon::parse('2026-01-23 08:00:00');
    $task->recurrence_rule = ['frequency' => 'yearly', 'interval' => 1];

    $nextDue = $task->calculateNextDueDate();

    // Unknown frequency falls through addInterval() default (addDays).
    expect($nextDue->format('Y-m-d H:i:s'))->toBe('2026-01-24 08:00:00');
});

test('calculateNextDueDate advances hourly through the no-more-times path', function () {
    $task = new Task;
    $task->due_at = Carbon::parse('2026-01-23 10:00:00');
    $task->recurrence_rule = [
        'frequency' => 'hourly',
        'interval' => 1,
        'times' => ['08:00', '10:00'],
    ];

    // No time later than 10:00 today, so addInterval(hourly) then reset to first time.
    $nextDue = $task->calculateNextDueDate();

    expect($nextDue->format('Y-m-d H:i:s'))->toBe('2026-01-23 08:00:00');
});

test('calculateNextDueDate advances weekly (no weekdays) through the no-more-times path', function () {
    $task = new Task;
    $task->due_at = Carbon::parse('2026-01-23 10:00:00');
    $task->recurrence_rule = [
        'frequency' => 'weekly',
        'interval' => 1,
        'times' => ['08:00', '10:00'],
    ];

    $nextDue = $task->calculateNextDueDate();

    expect($nextDue->format('Y-m-d H:i:s'))->toBe('2026-01-30 08:00:00');
});

test('calculateNextDueDate advances monthly through the no-more-times path', function () {
    $task = new Task;
    $task->due_at = Carbon::parse('2026-01-23 10:00:00');
    $task->recurrence_rule = [
        'frequency' => 'monthly',
        'interval' => 1,
        'times' => ['08:00', '10:00'],
    ];

    $nextDue = $task->calculateNextDueDate();

    expect($nextDue->format('Y-m-d H:i:s'))->toBe('2026-02-23 08:00:00');
});

// ---------------------------------------------------------------------------
// Timezone accessors
// ---------------------------------------------------------------------------

test('dueAt accessor converts the stored UTC value into the recurrence timezone', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'recurrence_timezone' => 'Europe/Berlin',
        'due_at' => '2026-01-15 08:00:00', // UTC
    ]);

    $due = $task->fresh()->due_at;

    // Winter in Berlin is UTC+1.
    expect($due->format('Y-m-d H:i:s'))->toBe('2026-01-15 09:00:00');
    expect($due->getTimezone()->getName())->toBe('Europe/Berlin');
});

test('completedAt accessor converts the stored UTC value into the recurrence timezone', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'recurrence_timezone' => 'Europe/Berlin',
        'completed_at' => '2026-01-15 08:00:00',
    ]);

    $completed = $task->fresh()->completed_at;

    expect($completed->format('Y-m-d H:i:s'))->toBe('2026-01-15 09:00:00');
    expect($completed->getTimezone()->getName())->toBe('Europe/Berlin');
});

test('lastNotifiedAt accessor converts the stored UTC value into the recurrence timezone', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'recurrence_timezone' => 'Europe/Berlin',
        'last_notified_at' => '2026-01-15 08:00:00',
    ]);

    $lastNotified = $task->fresh()->last_notified_at;

    expect($lastNotified->format('Y-m-d H:i:s'))->toBe('2026-01-15 09:00:00');
    expect($lastNotified->getTimezone()->getName())->toBe('Europe/Berlin');
});

test('timezone accessors return null when the underlying value is null', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'recurrence_timezone' => 'Europe/Berlin',
        'due_at' => null,
        'completed_at' => null,
        'last_notified_at' => null,
    ]);

    $task = $task->fresh();

    expect($task->due_at)->toBeNull();
    expect($task->completed_at)->toBeNull();
    expect($task->last_notified_at)->toBeNull();
});

test('timezone accessors leave the value untouched without a recurrence timezone', function () {
    $task = Task::factory()->create([
        'recurrence_rule' => null,
        'recurrence_timezone' => null,
        'due_at' => '2026-01-15 08:00:00',
    ]);

    $due = $task->fresh()->due_at;

    expect($due->format('Y-m-d H:i:s'))->toBe('2026-01-15 08:00:00');
});
