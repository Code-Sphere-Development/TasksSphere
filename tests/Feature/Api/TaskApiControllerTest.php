<?php

use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| index — GET /api/tasks
|--------------------------------------------------------------------------
*/

test('index requires authentication', function () {
    $this->getJson('/api/tasks')->assertUnauthorized();
});

test('index returns only the authenticated users active tasks ordered by due_at', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $later = Task::factory()->for($user)->create(['due_at' => now()->addDays(5), 'is_archived' => false, 'completed_at' => null]);
    $sooner = Task::factory()->for($user)->create(['due_at' => now()->addDay(), 'is_archived' => false, 'completed_at' => null]);

    // Excluded: archived, completed, and another user's task.
    Task::factory()->for($user)->create(['is_archived' => true]);
    Task::factory()->for($user)->create(['completed_at' => now()]);
    Task::factory()->for($other)->create(['is_archived' => false, 'completed_at' => null]);

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/tasks')
        ->assertOk()
        ->assertJsonCount(2);

    // Ordered by due_at ascending.
    $ids = collect($response->json())->pluck('id')->all();
    expect($ids)->toBe([$sooner->id, $later->id]);
});

/*
|--------------------------------------------------------------------------
| occurrences — GET /api/tasks/occurrences
|--------------------------------------------------------------------------
*/

test('occurrences requires authentication', function () {
    $this->getJson('/api/tasks/occurrences')->assertUnauthorized();
});

test('occurrences returns occurrences only for the authenticated user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Task::factory()->for($user)->create([
        'due_at' => now()->addDay(),
        'is_archived' => false,
        'completed_at' => null,
        'recurrence_rule' => null,
    ]);
    Task::factory()->for($other)->create([
        'due_at' => now()->addDay(),
        'is_archived' => false,
        'completed_at' => null,
        'recurrence_rule' => null,
    ]);

    Sanctum::actingAs($user);

    $this->getJson('/api/tasks/occurrences')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonStructure([['task', 'planned_at', 'is_completed']]);
});

test('occurrences respects explicit start and end query params', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create([
        'due_at' => now()->addDay(),
        'is_archived' => false,
        'completed_at' => null,
        'recurrence_rule' => null,
    ]);

    Sanctum::actingAs($user);

    $start = now()->addDays(3)->toDateTimeString();
    $end = now()->addDays(10)->toDateTimeString();

    // The task is due tomorrow — before the custom [+3d, +10d] window — so
    // getOccurrences() surfaces it via the "overdue" branch (planned_at = due_at).
    $this->getJson('/api/tasks/occurrences?start='.urlencode($start).'&end='.urlencode($end))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.is_completed', false);
});

/*
|--------------------------------------------------------------------------
| completed — GET /api/tasks/completed
|--------------------------------------------------------------------------
*/

test('completed requires authentication', function () {
    $this->getJson('/api/tasks/completed')->assertUnauthorized();
});

test('completed returns non-skipped completions for the users tasks only', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $task = Task::factory()->for($user)->create();
    TaskCompletion::factory()->count(2)->for($task)->create();
    TaskCompletion::factory()->skipped()->for($task)->create();

    // Another user's completion must be excluded.
    $otherTask = Task::factory()->for($other)->create();
    TaskCompletion::factory()->for($otherTask)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/tasks/completed')
        ->assertOk()
        ->assertJsonCount(2);
});

test('completed caps the result at 10 entries', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();
    TaskCompletion::factory()->count(12)->for($task)->create();

    Sanctum::actingAs($user);

    $this->getJson('/api/tasks/completed')
        ->assertOk()
        ->assertJsonCount(10);
});

/*
|--------------------------------------------------------------------------
| store — POST /api/tasks
|--------------------------------------------------------------------------
*/

test('store requires authentication', function () {
    $this->postJson('/api/tasks', ['title' => 'Test'])->assertUnauthorized();
});

test('store creates a task for the authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Buy milk',
        'description' => 'From the store',
    ])
        ->assertCreated()
        ->assertJsonPath('title', 'Buy milk');

    $this->assertDatabaseHas('tasks', [
        'title' => 'Buy milk',
        'user_id' => $user->id,
    ]);
});

test('store fails validation when title is missing', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', ['description' => 'no title'])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

test('store fails validation for an invalid recurrence frequency', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Recurring',
        'recurrence_rule' => ['frequency' => 'yearly'],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['recurrence_rule.frequency']);
});

test('store dispatches a reminder notification when notify is true', function () {
    Notification::fake();

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'Notify me',
        'notify' => true,
    ])->assertCreated();

    Notification::assertSentTo($user, TaskReminderNotification::class);
});

/*
|--------------------------------------------------------------------------
| show — GET /api/tasks/{task}
|--------------------------------------------------------------------------
*/

test('show requires authentication', function () {
    $task = Task::factory()->create();
    $this->getJson("/api/tasks/{$task->id}")->assertUnauthorized();
});

test('show returns the task for its owner', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->getJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('id', $task->id);
});

test('show forbids access to another users task', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/tasks/{$task->id}")->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| update — PUT /api/tasks/{task}
|--------------------------------------------------------------------------
*/

test('update requires authentication', function () {
    $task = Task::factory()->create();
    $this->putJson("/api/tasks/{$task->id}", ['title' => 'X'])->assertUnauthorized();
});

test('update modifies the owners task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['title' => 'Old']);

    Sanctum::actingAs($user);

    $this->putJson("/api/tasks/{$task->id}", ['title' => 'New title'])
        ->assertOk()
        ->assertJsonPath('title', 'New title');

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'title' => 'New title']);
});

test('update forbids modifying another users task', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create();

    Sanctum::actingAs(User::factory()->create());

    $this->putJson("/api/tasks/{$task->id}", ['title' => 'Hijacked'])->assertForbidden();
});

test('update fails validation when title exceeds 255 characters', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->putJson("/api/tasks/{$task->id}", ['title' => str_repeat('a', 256)])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title']);
});

/*
|--------------------------------------------------------------------------
| complete — POST /api/tasks/{task}/complete
|--------------------------------------------------------------------------
*/

test('complete requires authentication', function () {
    $task = Task::factory()->create();
    $this->postJson("/api/tasks/{$task->id}/complete")->assertUnauthorized();
});

test('complete marks a non-recurring task as completed', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['completed_at' => null]);

    Sanctum::actingAs($user);

    $this->postJson("/api/tasks/{$task->id}/complete")
        ->assertOk()
        ->assertJsonPath('message', 'Task completed');

    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('complete records a completion for a recurring task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->recurring('daily', 1)->create([
        'due_at' => now()->startOfDay(),
    ]);
    $plannedAt = $task->due_at->toDateTimeString();

    Sanctum::actingAs($user);

    $this->postJson("/api/tasks/{$task->id}/complete", ['planned_at' => $plannedAt])
        ->assertOk()
        ->assertJsonPath('message', 'Task completed');

    $this->assertDatabaseHas('task_completions', [
        'task_id' => $task->id,
        'is_skipped' => false,
    ]);
});

test('complete forbids completing another users task', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create();

    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/tasks/{$task->id}/complete")->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| skip — POST /api/tasks/{task}/skip
|--------------------------------------------------------------------------
*/

test('skip requires authentication', function () {
    $task = Task::factory()->create();
    $this->postJson("/api/tasks/{$task->id}/skip", ['planned_at' => now()->toDateTimeString()])
        ->assertUnauthorized();
});

test('skip records a skipped completion for a recurring task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->recurring('daily', 1)->create([
        'due_at' => now()->startOfDay(),
    ]);
    $plannedAt = $task->due_at->toDateTimeString();

    Sanctum::actingAs($user);

    $this->postJson("/api/tasks/{$task->id}/skip", ['planned_at' => $plannedAt])
        ->assertOk()
        ->assertJsonPath('message', 'Occurrence skipped');

    $this->assertDatabaseHas('task_completions', [
        'task_id' => $task->id,
        'is_skipped' => true,
    ]);
});

test('skip fails validation when planned_at is missing', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->postJson("/api/tasks/{$task->id}/skip", [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['planned_at']);
});

test('skip forbids skipping another users task', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create();

    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/tasks/{$task->id}/skip", ['planned_at' => now()->toDateTimeString()])
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| destroy — DELETE /api/tasks/{task}
|--------------------------------------------------------------------------
*/

test('destroy requires authentication', function () {
    $task = Task::factory()->create();
    $this->deleteJson("/api/tasks/{$task->id}")->assertUnauthorized();
});

test('destroy soft deletes the owners task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/tasks/{$task->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Task deleted');

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

test('destroy forbids deleting another users task', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->for($owner)->create();

    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson("/api/tasks/{$task->id}")->assertForbidden();

    $this->assertDatabaseHas('tasks', ['id' => $task->id, 'deleted_at' => null]);
});

test('store converts due_at from recurrence timezone to UTC', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', [
        'title' => 'TZ Task',
        'due_at' => '2026-03-01 10:00:00',
        'recurrence_timezone' => 'Europe/Berlin',
    ])->assertCreated();

    // Europe/Berlin in March is UTC+1 -> 10:00 local == 09:00 UTC
    expect($user->tasks()->first()->getRawOriginal('due_at'))->toBe('2026-03-01 09:00:00');
});

test('update converts due_at using recurrence timezone to UTC', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $task = Task::factory()->create(['user_id' => $user->id, 'recurrence_timezone' => null]);

    $this->putJson("/api/tasks/{$task->id}", [
        'title' => 'Updated',
        'due_at' => '2026-03-01 10:00:00',
        'recurrence_timezone' => 'Europe/Berlin',
    ])->assertOk();

    expect($task->fresh()->getRawOriginal('due_at'))->toBe('2026-03-01 09:00:00');
});
