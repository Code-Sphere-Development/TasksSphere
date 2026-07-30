<?php

use App\Models\Task;
use App\Models\User;
use App\Models\UserDevice;
use App\Notifications\TaskReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('reminder command sends notifications for due tasks', function () {
    Notification::fake();

    $user = User::factory()->create();
    $device = UserDevice::create([
        'user_id' => $user->id,
        'device_id' => 'device-123',
        'fcm_token' => 'fake-fcm-token',
    ]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Due Task',
        'due_at' => now()->subMinute(),
        'completed_at' => null,
        'last_notified_at' => null,
    ]);

    $this->artisan('tasks:send-reminders')
        ->expectsOutput("Benachrichtigung für Task ID {$task->id} an Benutzer {$user->email} gesendet.")
        ->assertExitCode(0);

    Notification::assertSentTo(
        $user,
        TaskReminderNotification::class,
        function ($notification, $channels) {
            return $channels === ['NotificationChannels\Fcm\FcmChannel'] || in_array('NotificationChannels\Fcm\FcmChannel', $channels);
        }
    );

    $task->refresh();
    $this->assertNotNull($task->last_notified_at);
});

test('reminder command does not send duplicate notifications', function () {
    Notification::fake();

    $user = User::factory()->create();
    UserDevice::create([
        'user_id' => $user->id,
        'fcm_token' => 'fake-fcm-token',
    ]);

    $dueAt = now()->subMinutes(10);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'due_at' => $dueAt,
        'last_notified_at' => $dueAt->addMinute(), // Already notified after due_at
    ]);

    $this->artisan('tasks:send-reminders')
        ->assertExitCode(0);

    Notification::assertNothingSent();
});
