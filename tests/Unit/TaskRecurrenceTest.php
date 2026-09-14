<?php

use App\Models\Task;
use Illuminate\Support\Carbon;

test('hourly recurrence preserves time', function () {
    $task = new Task;
    $task->due_at = Carbon::parse('2026-01-23 08:00:00');
    $task->recurrence_rule = ['frequency' => 'hourly', 'interval' => 12];

    $nextDue = $task->calculateNextDueDate();

    $this->assertEquals('2026-01-23 20:00:00', $nextDue->format('Y-m-d H:i:s'));
});

test('daily recurrence preserves time', function () {
    $task = new Task;
    $task->due_at = Carbon::parse('2026-01-23 08:00:00');
    $task->recurrence_rule = ['frequency' => 'daily', 'interval' => 1];

    $nextDue = $task->calculateNextDueDate();

    $this->assertEquals('2026-01-24 08:00:00', $nextDue->format('Y-m-d H:i:s'));
});

test('weekly recurrence preserves time', function () {
    $task = new Task;
    $task->due_at = Carbon::parse('2026-01-23 08:00:00');
    $task->recurrence_rule = ['frequency' => 'weekly', 'interval' => 1];

    $nextDue = $task->calculateNextDueDate();

    $this->assertEquals('2026-01-30 08:00:00', $nextDue->format('Y-m-d H:i:s'));
});

test('monthly recurrence preserves time', function () {
    $task = new Task;
    $task->due_at = Carbon::parse('2026-01-23 08:00:00');
    $task->recurrence_rule = ['frequency' => 'monthly', 'interval' => 1];

    $nextDue = $task->calculateNextDueDate();

    $this->assertEquals('2026-02-23 08:00:00', $nextDue->format('Y-m-d H:i:s'));
});

test('multiple times per day recurrence', function () {
    $task = new Task;
    $task->due_at = Carbon::parse('2026-01-23 08:00:00');
    $task->recurrence_rule = [
        'frequency' => 'daily',
        'interval' => 1,
        'times' => ['08:00', '10:00'],
    ];

    // 1. Completion -> should move to 10:00 same day
    $nextDue = $task->calculateNextDueDate();
    $this->assertEquals('2026-01-23 10:00:00', $nextDue->format('Y-m-d H:i:s'));

    // 2. Completion (simulated) -> should move to 08:00 next day
    $task->due_at = $nextDue;
    $nextDue2 = $task->calculateNextDueDate();
    $this->assertEquals('2026-01-24 08:00:00', $nextDue2->format('Y-m-d H:i:s'));
});

test('multiple times with larger interval', function () {
    $task = new Task;
    $task->due_at = Carbon::parse('2026-01-23 10:00:00');
    $task->recurrence_rule = [
        'frequency' => 'daily',
        'interval' => 2, // every 2 days
        'times' => ['08:00', '10:00'],
    ];

    // Currently at 10:00 (last time of the day)
    // Next should be 08:00 in 2 days
    $nextDue = $task->calculateNextDueDate();
    $this->assertEquals('2026-01-25 08:00:00', $nextDue->format('Y-m-d H:i:s'));
});

test('an hourly task yields every occurrence of the week, not just the first hundred', function () {
    $task = new Task;
    $task->due_at = Carbon::parse('2026-01-01 00:00:00');
    $task->recurrence_rule = ['frequency' => 'hourly', 'interval' => 1];
    $task->setRelation('completions', collect());

    $occurrences = $task->getOccurrences('2026-01-01 00:00:00', '2026-01-07 23:59:59');

    // 7 Tage stuendlich sind 168 Termine; die alte feste Grenze von 100 hat
    // ueber ein Drittel davon stillschweigend verschluckt.
    expect($occurrences->count())->toBeGreaterThan(160);
});

test('the occurrence cap is configurable and still bounded', function () {
    config(['tasks.max_occurrences_per_task' => 10]);

    $task = new Task;
    $task->due_at = Carbon::parse('2026-01-01 00:00:00');
    $task->recurrence_rule = ['frequency' => 'hourly', 'interval' => 1];
    $task->setRelation('completions', collect());

    expect($task->getOccurrences('2026-01-01 00:00:00', '2026-01-07 23:59:59')->count())
        ->toBeLessThanOrEqual(10);
});
