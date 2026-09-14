<?php

use App\Enums\TaskPriority;

beforeEach(function () {
    app()->setLocale('de');
});

test('every priority has a label', function () {
    expect(TaskPriority::Urgent->label())->toBe('Dringend');
    expect(TaskPriority::High->label())->toBe('Hoch');
    expect(TaskPriority::Medium->label())->toBe('Mittel');
    expect(TaskPriority::Low->label())->toBe('Niedrig');
});

test('priorities are ordered with the most urgent first', function () {
    expect(TaskPriority::Urgent->value)->toBeLessThan(TaskPriority::Low->value);
});

test('labels are translated for the english locale', function () {
    app()->setLocale('en');

    expect(TaskPriority::Urgent->label())->toBe('Urgent');
});
