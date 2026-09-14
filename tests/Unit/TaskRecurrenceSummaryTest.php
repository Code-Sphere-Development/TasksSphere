<?php

use App\Models\Task;

beforeEach(function () {
    app()->setLocale('de');
});

test('non recurring task has no recurrence summary', function () {
    $task = new Task;
    $task->recurrence_rule = null;

    expect($task->recurrenceSummary())->toBeNull();
});

test('hourly recurrence with interval one is summarized as hourly', function () {
    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'hourly', 'interval' => 1];

    expect($task->recurrenceSummary())->toBe('Stündlich');
});

test('hourly recurrence with larger interval names the interval', function () {
    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'hourly', 'interval' => 12];

    expect($task->recurrenceSummary())->toBe('Alle 12 Stunden');
});

test('daily recurrence with interval one is summarized as daily', function () {
    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'daily', 'interval' => 1];

    expect($task->recurrenceSummary())->toBe('Täglich');
});

test('daily recurrence with larger interval names the interval', function () {
    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'daily', 'interval' => 3];

    expect($task->recurrenceSummary())->toBe('Alle 3 Tage');
});

test('weekly recurrence with interval one is summarized as weekly', function () {
    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'weekly', 'interval' => 1];

    expect($task->recurrenceSummary())->toBe('Wöchentlich');
});

test('monthly recurrence with larger interval names the interval', function () {
    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'monthly', 'interval' => 2];

    expect($task->recurrenceSummary())->toBe('Alle 2 Monate');
});

test('recurrence summary is translated for the english locale', function () {
    app()->setLocale('en');

    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'daily', 'interval' => 3];

    expect($task->recurrenceSummary())->toBe('Every 3 days');
});

test('weekly recurrence lists its weekdays', function () {
    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'weekly', 'interval' => 1, 'weekdays' => [1, 4]];

    expect($task->recurrenceSummary())->toBe('Wöchentlich, Mo und Do');
});

test('weekdays are listed in calendar order regardless of input order', function () {
    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'weekly', 'interval' => 1, 'weekdays' => [7, 3, 1]];

    expect($task->recurrenceSummary())->toBe('Wöchentlich, Mo, Mi und So');
});

test('weekdays are ignored for non weekly frequencies', function () {
    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'daily', 'interval' => 1, 'weekdays' => [1, 4]];

    expect($task->recurrenceSummary())->toBe('Täglich');
});

test('a single recurrence time is appended', function () {
    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'daily', 'interval' => 1, 'times' => ['08:00']];

    expect($task->recurrenceSummary())->toBe('Täglich, um 08:00 Uhr');
});

test('multiple recurrence times are listed', function () {
    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'daily', 'interval' => 1, 'times' => ['08:00', '12:00', '17:00']];

    expect($task->recurrenceSummary())->toBe('Täglich, um 08:00, 12:00 und 17:00 Uhr');
});

test('the recurrence timezone is appended when set', function () {
    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'daily', 'interval' => 1, 'times' => ['08:00']];
    $task->recurrence_timezone = 'Europe/Berlin';

    expect($task->recurrenceSummary())->toBe('Täglich, um 08:00 Uhr (Europe/Berlin)');
});

test('weekdays and times are combined', function () {
    $task = new Task;
    $task->recurrence_rule = ['frequency' => 'weekly', 'interval' => 2, 'weekdays' => [1, 4], 'times' => ['08:00', '17:00']];
    $task->recurrence_timezone = 'Europe/Berlin';

    expect($task->recurrenceSummary())->toBe('Alle 2 Wochen, Mo und Do, um 08:00 und 17:00 Uhr (Europe/Berlin)');
});
