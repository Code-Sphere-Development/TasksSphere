<?php

use App\Models\User;
use App\Notifications\TaskReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('task creation triggers notification when requested', function () {
    Notification::fake();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/tasks', [
        'title' => 'Test Task',
        'notify' => true,
    ]);

    $response->assertStatus(201);

    Notification::assertSentTo(
        $user,
        TaskReminderNotification::class
    );
});

test('task creation does not trigger notification by default', function () {
    Notification::fake();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/tasks', [
        'title' => 'Test Task',
    ]);

    $response->assertStatus(201);

    Notification::assertNothingSent();
});
