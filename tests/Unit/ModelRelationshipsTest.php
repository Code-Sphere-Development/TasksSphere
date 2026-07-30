<?php

use App\Models\ListItem;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\TaskList;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

// ---------------------------------------------------------------------------
// TaskList — relations
// ---------------------------------------------------------------------------

test('task list belongs to a user', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->create(['user_id' => $user->id]);

    expect($list->user)->toBeInstanceOf(User::class);
    expect($list->user->id)->toBe($user->id);
});

test('task list has items ordered by position', function () {
    $list = TaskList::factory()->checklist()->create();
    ListItem::factory()->create(['task_list_id' => $list->id, 'position' => 2, 'title' => 'second']);
    ListItem::factory()->create(['task_list_id' => $list->id, 'position' => 1, 'title' => 'first']);

    $items = $list->items;

    expect($items)->toHaveCount(2);
    expect($items->first()->title)->toBe('first');
    expect($items->last()->title)->toBe('second');
    expect($items->first())->toBeInstanceOf(ListItem::class);
});

test('task list has tasks', function () {
    $list = TaskList::factory()->tasks()->create();
    Task::factory()->count(2)->create(['task_list_id' => $list->id]);

    expect($list->tasks)->toHaveCount(2);
    expect($list->tasks->first())->toBeInstanceOf(Task::class);
});

// ---------------------------------------------------------------------------
// TaskList — scopes
// ---------------------------------------------------------------------------

test('forUser scope returns only personal lists for the user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $personal = TaskList::factory()->create(['user_id' => $user->id, 'team_id' => null]);
    // Same user but assigned to a team must be excluded (whereNull team_id).
    TaskList::factory()->create(['user_id' => $user->id, 'team_id' => 5]);
    TaskList::factory()->create(['user_id' => $other->id, 'team_id' => null]);

    $result = TaskList::forUser($user->id)->get();

    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe($personal->id);
});

test('forTeam scope returns only lists for the team', function () {
    $inTeam = TaskList::factory()->create(['team_id' => 7]);
    TaskList::factory()->create(['team_id' => 8]);

    $result = TaskList::forTeam(7)->get();

    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe($inTeam->id);
});

test('ofType scope filters by list type', function () {
    $checklist = TaskList::factory()->checklist()->create();
    TaskList::factory()->tasks()->create();

    $result = TaskList::ofType('checklist')->get();

    expect($result)->toHaveCount(1);
    expect($result->first()->id)->toBe($checklist->id);
});

// ---------------------------------------------------------------------------
// TaskList — type helpers
// ---------------------------------------------------------------------------

test('isChecklist and isTaskList reflect the type', function () {
    $checklist = TaskList::factory()->checklist()->create();
    $taskList = TaskList::factory()->tasks()->create();

    expect($checklist->isChecklist())->toBeTrue();
    expect($checklist->isTaskList())->toBeFalse();

    expect($taskList->isTaskList())->toBeTrue();
    expect($taskList->isChecklist())->toBeFalse();
});

// ---------------------------------------------------------------------------
// TaskList — counts
// ---------------------------------------------------------------------------

test('itemCount and completedCount count items for a checklist', function () {
    $list = TaskList::factory()->checklist()->create();
    ListItem::factory()->count(2)->create(['task_list_id' => $list->id]);
    ListItem::factory()->completed()->create(['task_list_id' => $list->id]);

    expect($list->itemCount())->toBe(3);
    expect($list->completedCount())->toBe(1);
});

test('itemCount and completedCount count tasks for a task list', function () {
    $list = TaskList::factory()->tasks()->create();
    Task::factory()->count(2)->create([
        'task_list_id' => $list->id,
        'completed_at' => null,
    ]);
    Task::factory()->create([
        'task_list_id' => $list->id,
        'completed_at' => now(),
    ]);

    expect($list->itemCount())->toBe(3);
    expect($list->completedCount())->toBe(1);
});

// ---------------------------------------------------------------------------
// ListItem
// ---------------------------------------------------------------------------

test('list item belongs to a task list', function () {
    $list = TaskList::factory()->create();
    $item = ListItem::factory()->create(['task_list_id' => $list->id]);

    expect($item->taskList)->toBeInstanceOf(TaskList::class);
    expect($item->taskList->id)->toBe($list->id);
});

test('list item casts is_completed and position', function () {
    $item = ListItem::factory()->create([
        'is_completed' => 1,
        'position' => '4',
    ]);

    $item = $item->fresh();

    expect($item->is_completed)->toBeBool()->toBeTrue();
    expect($item->position)->toBeInt()->toBe(4);
});

// ---------------------------------------------------------------------------
// UserDevice
// ---------------------------------------------------------------------------

test('user device belongs to a user', function () {
    $user = User::factory()->create();
    $device = UserDevice::factory()->create(['user_id' => $user->id]);

    expect($device->user)->toBeInstanceOf(User::class);
    expect($device->user->id)->toBe($user->id);
});

// ---------------------------------------------------------------------------
// TaskCompletion
// ---------------------------------------------------------------------------

test('task completion belongs to a task', function () {
    $task = Task::factory()->create();
    $completion = TaskCompletion::factory()->create(['task_id' => $task->id]);

    expect($completion->task)->toBeInstanceOf(Task::class);
    expect($completion->task->id)->toBe($task->id);
});

test('task completion casts dates and the skipped flag', function () {
    $completion = TaskCompletion::factory()->skipped()->create([
        'planned_at' => '2026-01-23 08:00:00',
    ]);

    $completion = $completion->fresh();

    expect($completion->planned_at)->toBeInstanceOf(Carbon::class);
    expect($completion->completed_at)->toBeNull();
    expect($completion->is_skipped)->toBeBool()->toBeTrue();
});

// ---------------------------------------------------------------------------
// User — relations & helpers
// ---------------------------------------------------------------------------

test('user has many tasks', function () {
    $user = User::factory()->create();
    Task::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->tasks)->toHaveCount(2);
    expect($user->tasks->first())->toBeInstanceOf(Task::class);
});

test('user has many devices', function () {
    $user = User::factory()->create();
    UserDevice::factory()->count(2)->create(['user_id' => $user->id]);

    expect($user->devices)->toHaveCount(2);
    expect($user->devices->first())->toBeInstanceOf(UserDevice::class);
});

test('name accessor joins first and last name', function () {
    $user = User::factory()->create([
        'first_name' => 'Ada',
        'last_name' => 'Lovelace',
    ]);

    expect($user->name)->toBe('Ada Lovelace');
});

test('routeNotificationForFcm returns the tokens of all devices', function () {
    $user = User::factory()->create();
    UserDevice::factory()->create(['user_id' => $user->id, 'fcm_token' => 'token-a']);
    UserDevice::factory()->create(['user_id' => $user->id, 'fcm_token' => 'token-b']);

    $tokens = $user->routeNotificationForFcm();

    expect($tokens)->toBeArray()->toContain('token-a', 'token-b');
});

test('updateFcmToken creates a device keyed by device id', function () {
    $user = User::factory()->create();

    $user->updateFcmToken('fcm-123', 'device-xyz');

    $device = $user->devices()->first();
    expect($device->device_id)->toBe('device-xyz');
    expect($device->fcm_token)->toBe('fcm-123');
});

test('updateFcmToken updates the token for an existing device id', function () {
    $user = User::factory()->create();
    UserDevice::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'device-xyz',
        'fcm_token' => 'old-token',
    ]);

    $user->updateFcmToken('new-token', 'device-xyz');

    expect($user->devices()->count())->toBe(1);
    expect($user->devices()->first()->fcm_token)->toBe('new-token');
});

test('updateFcmToken removes the token from other users', function () {
    $other = User::factory()->create();
    UserDevice::factory()->create([
        'user_id' => $other->id,
        'device_id' => 'device-other',
        'fcm_token' => 'shared-token',
    ]);

    $user = User::factory()->create();
    $user->updateFcmToken('shared-token', 'device-mine');

    // The token is stolen away from the other user.
    expect($other->devices()->count())->toBe(0);
    expect($user->devices()->first()->fcm_token)->toBe('shared-token');
});

test('updateFcmToken keys by token when no device id is given', function () {
    $user = User::factory()->create();

    $user->updateFcmToken('token-only');

    $device = $user->devices()->first();
    expect($device->fcm_token)->toBe('token-only');
    expect($device->device_id)->toBeNull();
});

test('updateFcmToken is a no-op for an empty token', function () {
    $user = User::factory()->create();

    $user->updateFcmToken(null);
    $user->updateFcmToken('');

    expect($user->devices()->count())->toBe(0);
});
