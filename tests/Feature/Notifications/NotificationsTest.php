<?php

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use App\Notifications\TestFcmNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| TaskReminderNotification
|--------------------------------------------------------------------------
*/

test('task reminder via returns the fcm channel', function () {
    $task = Task::factory()->create();
    $user = User::factory()->create();

    $notification = new TaskReminderNotification($task);

    expect($notification->via($user))->toBe([FcmChannel::class]);
});

test('task reminder toFcm builds a german message by default', function () {
    $task = Task::factory()->create(['title' => 'Buy milk']);
    $user = User::factory()->create(['language' => 'de']);

    $message = (new TaskReminderNotification($task))->toFcm($user);

    expect($message)->toBeInstanceOf(FcmMessage::class);
    expect($message->notification)->toBeInstanceOf(FcmNotification::class);
    expect($message->notification->title)->toBe('Aufgabe fällig!');
    expect($message->notification->body)->toBe('Buy milk');
    expect($message->data)->toBe(['task_id' => (string) $task->id]);
});

test('task reminder toFcm builds an english message when language is en', function () {
    $task = Task::factory()->create(['title' => 'Buy milk']);
    $user = User::factory()->create(['language' => 'en']);

    $message = (new TaskReminderNotification($task))->toFcm($user);

    expect($message->notification->title)->toBe('Task due!');
    expect($message->notification->body)->toBe('Buy milk');
});

test('task reminder toFcm falls back to german when language is null', function () {
    $task = Task::factory()->create();
    $user = User::factory()->create();
    $user->language = null;

    $message = (new TaskReminderNotification($task))->toFcm($user);

    expect($message->notification->title)->toBe('Aufgabe fällig!');
});

test('task reminder toFcm falls back to german for an unknown language', function () {
    $task = Task::factory()->create();
    $user = User::factory()->create(['language' => 'fr']);

    $message = (new TaskReminderNotification($task))->toFcm($user);

    // Only 'en' produces the english title; anything else is treated as german.
    expect($message->notification->title)->toBe('Aufgabe fällig!');
});

test('task reminder toFcm casts the task id to a string in the data payload', function () {
    $task = Task::factory()->create();
    $user = User::factory()->create();

    $message = (new TaskReminderNotification($task))->toFcm($user);

    expect($message->data['task_id'])->toBeString()
        ->and($message->data['task_id'])->toBe((string) $task->id);
});

test('task reminder toArray returns the task id and title', function () {
    $task = Task::factory()->create(['title' => 'Water plants']);
    $user = User::factory()->create();

    $array = (new TaskReminderNotification($task))->toArray($user);

    expect($array)->toBe([
        'task_id' => $task->id,
        'title' => 'Water plants',
    ]);
    expect($array['task_id'])->toBeInt();
});

/*
|--------------------------------------------------------------------------
| TestFcmNotification
|--------------------------------------------------------------------------
*/

test('test fcm via returns the fcm channel', function () {
    $user = User::factory()->create();

    $notification = new TestFcmNotification;

    expect($notification->via($user))->toBe([FcmChannel::class]);
});

test('test fcm toFcm uses the default title and message', function () {
    $user = User::factory()->create();

    $message = (new TestFcmNotification)->toFcm($user);

    expect($message)->toBeInstanceOf(FcmMessage::class);
    expect($message->notification)->toBeInstanceOf(FcmNotification::class);
    expect($message->notification->title)->toBe('Test Benachrichtigung');
    expect($message->notification->body)->toBe('Dies ist eine Test-Nachricht von TasksSphere.');
    expect($message->data)->toBe(['type' => 'test']);
});

test('test fcm toFcm uses the custom title and message', function () {
    $user = User::factory()->create();

    $message = (new TestFcmNotification('Custom Title', 'Custom body'))->toFcm($user);

    expect($message->notification->title)->toBe('Custom Title');
    expect($message->notification->body)->toBe('Custom body');
    expect($message->data)->toBe(['type' => 'test']);
});
