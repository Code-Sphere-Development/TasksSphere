<?php

use App\Enums\TaskRotation;
use App\Models\Household;
use App\Models\Task;
use App\Models\User;
use App\Models\UserDevice;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(fn () => Notification::fake());

/** Ohne Geraet liefert via() keinen Kanal, und der Fake zeichnet nichts auf. */
function reachableUser(): User
{
    $user = User::factory()->create(['notify_push' => true]);
    UserDevice::factory()->create(['user_id' => $user->id]);

    return $user;
}

test('the assignee is notified when a task is created for them via the api', function () {
    $owner = reachableUser();
    $member = reachableUser();
    Household::createFor($owner, 'Familie')->addMember($member);
    Sanctum::actingAs($owner);

    $this->postJson('/api/tasks', ['title' => 'Muell', 'assigned_to' => $member->id])->assertCreated();

    Notification::assertSentToTimes($member, TaskAssignedNotification::class, 1);
    Notification::assertNotSentTo($owner, TaskAssignedNotification::class);
});

test('reassigning notifies the new assignee only', function () {
    $owner = reachableUser();
    $a = reachableUser();
    $b = reachableUser();
    $task = Task::factory()->for($owner)->create(['assigned_to' => $a->id]);
    Notification::fake();

    $this->actingAs($owner);
    $task->update(['title' => 'Nur umbenannt']);
    Notification::assertNothingSent();

    $task->update(['assigned_to' => $b->id]);
    Notification::assertSentTo($b, TaskAssignedNotification::class);
    Notification::assertNotSentTo($a, TaskAssignedNotification::class);
});

test('assigning a task to yourself sends nothing', function () {
    $owner = reachableUser();
    $this->actingAs($owner);

    Task::factory()->for($owner)->create()->assignTo($owner, $owner);

    Notification::assertNothingSent();
});

test('rotation after completion notifies the person whose turn it is', function () {
    $a = reachableUser();
    $b = reachableUser();
    $task = Task::factory()->for($a)->create([
        'due_at' => '2026-01-23 08:00:00',
        'recurrence_rule' => ['frequency' => 'daily', 'interval' => 1],
        'rotation_strategy' => TaskRotation::LeastCompleted,
        'assigned_to' => $a->id,
    ]);
    $task->assignees()->attach([$a->id => ['assigned_by' => $a->id], $b->id => ['assigned_by' => $a->id]]);

    $this->actingAs($a);
    $task->fresh()->complete(null, $a);

    Notification::assertSentTo($b, TaskAssignedNotification::class);
});

test('the push is only delivered when the assignee wants it and has a device', function () {
    $user = User::factory()->create(['notify_push' => true]);
    $notification = new TaskAssignedNotification(Task::factory()->create());

    expect($notification->via($user))->toBe([]);

    UserDevice::factory()->create(['user_id' => $user->id]);
    expect($notification->via($user->fresh()))->not->toBe([]);

    $user->update(['notify_push' => false]);
    expect($notification->via($user->fresh()))->toBe([]);
});
