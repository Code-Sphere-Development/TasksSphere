<?php

use App\Livewire\TaskManager;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/*
 * mount()
 */
test('mount uses the user timezone when set', function () {
    $user = User::factory()->create(['timezone' => 'America/New_York']);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->assertSet('recurrence_timezone', 'America/New_York');
});

test('mount falls back to Europe/Berlin when user has no timezone', function () {
    $user = User::factory()->create(['timezone' => null]);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->assertSet('recurrence_timezone', 'Europe/Berlin');
});

/*
 * updateTimezone()
 */
test('updateTimezone persists a new timezone on the user', function () {
    $user = User::factory()->create(['timezone' => 'Europe/Berlin']);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->call('updateTimezone', 'Asia/Tokyo')
        ->assertSet('recurrence_timezone', 'Asia/Tokyo');

    expect($user->fresh()->timezone)->toBe('Asia/Tokyo');
});

test('updateTimezone does nothing when the timezone is unchanged', function () {
    $user = User::factory()->create(['timezone' => 'Europe/Berlin']);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->call('updateTimezone', 'Europe/Berlin')
        ->assertSet('recurrence_timezone', 'Europe/Berlin');

    expect($user->fresh()->timezone)->toBe('Europe/Berlin');
});

/*
 * updatedRecurrenceTimezone()
 */
test('setting recurrence_timezone updates the user timezone', function () {
    $user = User::factory()->create(['timezone' => 'Europe/Berlin']);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->set('recurrence_timezone', 'America/New_York');

    expect($user->fresh()->timezone)->toBe('America/New_York');
});

/*
 * addTime() / removeTime()
 */
test('addTime appends a valid time, keeps it sorted and clears newTime', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->set('newTime', '09:00')
        ->call('addTime')
        ->set('newTime', '08:00')
        ->call('addTime')
        ->assertSet('times', ['08:00', '09:00'])
        ->assertSet('newTime', '');
});

test('addTime does not add a duplicate time', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->set('times', ['08:00'])
        ->set('newTime', '08:00')
        ->call('addTime')
        ->assertSet('times', ['08:00']);
});

test('addTime rejects an invalid time format', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->set('newTime', '99:99')
        ->call('addTime')
        ->assertHasErrors('newTime')
        ->assertSet('times', []);
});

test('removeTime removes by index and reindexes the array', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->set('times', ['08:00', '09:00', '10:00'])
        ->call('removeTime', 1)
        ->assertSet('times', ['08:00', '10:00']);
});

/*
 * showCreateForm()
 */
test('showCreateForm opens the form and clears any active edit', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'recurrence_rule' => null]);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->call('editTask', $task->id)
        ->assertSet('isEditing', true)
        ->call('showCreateForm')
        ->assertSet('isEditing', false)
        ->assertSet('showForm', true);
});

/*
 * createTask()
 */
test('createTask creates a non-recurring task and resets the form', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->set('title', 'Buy milk')
        ->set('description', 'Two liters')
        ->set('frequency', 'none')
        ->call('createTask')
        ->assertSet('title', null)
        ->assertSet('showForm', false);

    $task = Task::where('title', 'Buy milk')->first();
    expect($task)->not->toBeNull()
        ->and($task->user_id)->toBe($user->id)
        ->and($task->recurrence_rule)->toBeNull()
        ->and($task->due_at)->toBeNull();
});

test('createTask creates a recurring daily task with times', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->set('title', 'Take pills')
        ->set('frequency', 'daily')
        ->set('interval', 2)
        ->set('times', ['08:00'])
        ->call('createTask');

    $task = Task::where('title', 'Take pills')->first();
    expect($task)->not->toBeNull()
        ->and($task->recurrence_rule['frequency'])->toBe('daily')
        ->and($task->recurrence_rule['interval'])->toBe(2)
        ->and($task->recurrence_rule['times'])->toBe(['08:00'])
        ->and($task->recurrence_rule['weekdays'])->toBe([])
        ->and($task->due_at)->not->toBeNull();
});

test('createTask stores weekdays only for weekly frequency', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->set('title', 'Gym')
        ->set('frequency', 'weekly')
        ->set('interval', 1)
        ->set('weekdays', [1, 3])
        ->set('times', ['18:00'])
        ->call('createTask');

    $task = Task::where('title', 'Gym')->first();
    expect($task->recurrence_rule['frequency'])->toBe('weekly')
        ->and($task->recurrence_rule['weekdays'])->toBe([1, 3]);
});

test('createTask validates that a title is required', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->set('title', '')
        ->call('createTask')
        ->assertHasErrors('title');

    expect(Task::count())->toBe(0);
});

/*
 * editTask()
 */
test('editTask loads a non-recurring task into the form', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Original title',
        'recurrence_rule' => null,
    ]);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->call('editTask', $task->id)
        ->assertSet('title', 'Original title')
        ->assertSet('frequency', 'none')
        ->assertSet('isEditing', true);
});

test('editTask loads a recurring task rule into the form', function () {
    $user = User::factory()->create();
    $task = Task::factory()->recurring('weekly', 3)->create([
        'user_id' => $user->id,
        'recurrence_rule' => [
            'frequency' => 'weekly',
            'interval' => 3,
            'times' => ['07:30'],
            'weekdays' => [2, 4],
        ],
    ]);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->call('editTask', $task->id)
        ->assertSet('frequency', 'weekly')
        ->assertSet('interval', 3)
        ->assertSet('times', ['07:30'])
        ->assertSet('weekdays', [2, 4])
        ->assertSet('isEditing', true);
});

test('editTask cannot load another users task', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $owner->id]);
    $this->actingAs($other);

    expect(fn () => Livewire::test(TaskManager::class)->call('editTask', $task->id))
        ->toThrow(ModelNotFoundException::class);
});

/*
 * updateTask()
 */
test('updateTask persists changes and clears the edit state', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Before',
        'recurrence_rule' => null,
    ]);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->call('editTask', $task->id)
        ->set('title', 'After')
        ->call('updateTask')
        ->assertSet('isEditing', false)
        ->assertSet('title', null);

    expect($task->fresh()->title)->toBe('After');
});

/*
 * cancelEdit()
 */
test('cancelEdit resets the form state', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'recurrence_rule' => null]);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->call('editTask', $task->id)
        ->assertSet('isEditing', true)
        ->call('cancelEdit')
        ->assertSet('isEditing', false)
        ->assertSet('title', null)
        ->assertSet('showForm', false);
});

/*
 * completeTask()
 */
test('completeTask marks a non-recurring task as completed', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'recurrence_rule' => null,
        'completed_at' => null,
    ]);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->call('completeTask', $task->id);

    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('completeTask creates a completion record for a recurring task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->recurring('daily', 1)->create([
        'user_id' => $user->id,
        'recurrence_timezone' => 'UTC',
        'due_at' => now()->startOfHour(),
    ]);
    $this->actingAs($user);

    $plannedAt = now()->startOfHour()->toDateTimeString();

    Livewire::test(TaskManager::class)
        ->call('completeTask', $task->id, $plannedAt);

    $this->assertDatabaseHas('task_completions', [
        'task_id' => $task->id,
        'is_skipped' => false,
    ]);
    expect($task->completions()->count())->toBe(1);
});

/*
 * deleteTask()
 */
test('deleteTask deletes a non-recurring task immediately', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'recurrence_rule' => null]);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->call('deleteTask', $task->id)
        ->assertSet('confirmingTaskDeletion', false);

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

test('deleteTask on a recurring occurrence opens the confirmation dialog', function () {
    $user = User::factory()->create();
    $task = Task::factory()->recurring('daily', 1)->create([
        'user_id' => $user->id,
        'recurrence_timezone' => 'UTC',
        'due_at' => now()->startOfHour(),
    ]);
    $this->actingAs($user);

    $plannedAt = now()->startOfHour()->toDateTimeString();

    Livewire::test(TaskManager::class)
        ->call('deleteTask', $task->id, $plannedAt)
        ->assertSet('confirmingTaskDeletion', true)
        ->assertSet('deletionTaskId', $task->id)
        ->assertSet('deletionPlannedAt', $plannedAt);

    // The task itself must not be deleted yet.
    $this->assertNotSoftDeleted('tasks', ['id' => $task->id]);
});

/*
 * deleteOccurrence()
 */
test('deleteOccurrence skips a single recurring occurrence', function () {
    $user = User::factory()->create();
    $task = Task::factory()->recurring('daily', 1)->create([
        'user_id' => $user->id,
        'recurrence_timezone' => 'UTC',
        'due_at' => now()->startOfHour(),
    ]);
    $this->actingAs($user);

    $plannedAt = now()->startOfHour()->toDateTimeString();

    Livewire::test(TaskManager::class)
        ->call('deleteTask', $task->id, $plannedAt)
        ->call('deleteOccurrence')
        ->assertSet('confirmingTaskDeletion', false)
        ->assertSet('deletionTaskId', null);

    $this->assertDatabaseHas('task_completions', [
        'task_id' => $task->id,
        'is_skipped' => true,
    ]);
    $this->assertNotSoftDeleted('tasks', ['id' => $task->id]);
});

/*
 * deleteAll()
 */
test('deleteAll deletes the whole recurring task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->recurring('daily', 1)->create([
        'user_id' => $user->id,
        'recurrence_timezone' => 'UTC',
        'due_at' => now()->startOfHour(),
    ]);
    $this->actingAs($user);

    $plannedAt = now()->startOfHour()->toDateTimeString();

    Livewire::test(TaskManager::class)
        ->call('deleteTask', $task->id, $plannedAt)
        ->call('deleteAll')
        ->assertSet('confirmingTaskDeletion', false);

    $this->assertSoftDeleted('tasks', ['id' => $task->id]);
});

/*
 * cancelDeletion()
 */
test('cancelDeletion resets the deletion confirmation state', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->set('confirmingTaskDeletion', true)
        ->set('deletionTaskId', 5)
        ->set('deletionPlannedAt', now()->toDateTimeString())
        ->call('cancelDeletion')
        ->assertSet('confirmingTaskDeletion', false)
        ->assertSet('deletionTaskId', null)
        ->assertSet('deletionPlannedAt', null);
});

/*
 * render()
 */
test('render only lists tasks for the authenticated user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'My own task',
        'recurrence_rule' => null,
        'is_archived' => false,
    ]);
    Task::factory()->create([
        'user_id' => $other->id,
        'title' => 'Someone elses task',
        'recurrence_rule' => null,
        'is_archived' => false,
    ]);

    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->assertSee('My own task')
        ->assertDontSee('Someone elses task');
});

test('render excludes archived tasks', function () {
    $user = User::factory()->create();
    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Archived task',
        'recurrence_rule' => null,
        'is_archived' => true,
    ]);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->assertViewHas('tasks', fn ($tasks) => $tasks->isEmpty());
});

test('render counts todays open occurrences', function () {
    $user = User::factory()->create();
    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Due today',
        'recurrence_rule' => null,
        'recurrence_timezone' => null,
        'due_at' => now(),
        'completed_at' => null,
        'is_archived' => false,
    ]);
    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->assertViewHas('todayCount', fn ($count) => $count === 1);
});

test('render lists recent non-skipped completions only', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'recurrence_rule' => null,
        'is_archived' => false,
    ]);

    TaskCompletion::factory()->create([
        'task_id' => $task->id,
        'is_skipped' => false,
    ]);
    TaskCompletion::factory()->skipped()->create([
        'task_id' => $task->id,
    ]);

    $this->actingAs($user);

    Livewire::test(TaskManager::class)
        ->assertViewHas('completedCompletions', fn ($c) => $c->count() === 1 && $c->every(fn ($x) => ! $x->is_skipped));
});

test('updateTask converts an existing task into a recurring weekly task', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'recurrence_rule' => null,
    ]);

    Livewire::test(TaskManager::class)
        ->call('editTask', $task->id)
        ->set('title', 'Now Recurring')
        ->set('frequency', 'weekly')
        ->set('interval', 1)
        ->set('weekdays', [1, 3])
        ->call('updateTask');

    $rule = $task->fresh()->recurrence_rule;
    expect($rule['frequency'])->toBe('weekly')
        ->and($rule['weekdays'])->toBe([1, 3])
        ->and($rule['interval'])->toBe(1);
});
