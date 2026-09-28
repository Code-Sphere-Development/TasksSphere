<?php

use App\Livewire\TaskManager;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Aufgaben nachtraeglich einer Liste zuordnen
|--------------------------------------------------------------------------
| Die Zuordnung war bisher nur beim Blick aus der Liste heraus moeglich und
| dort auch nur fuer Aufgaben ohne Liste. Ueber das Formular laesst sie sich
| jetzt jederzeit setzen, aendern und wieder loesen.
*/

function taskListFor(User $user, string $title = 'Haushalt'): TaskList
{
    return TaskList::factory()->tasks()->create(['user_id' => $user->id, 'title' => $title]);
}

function plainTask(User $user, array $attributes = []): Task
{
    return Task::factory()->for($user)->create(array_merge([
        'title' => 'Regal aufbauen',
        'due_at' => null,
        'recurrence_rule' => null,
        'recurrence_timezone' => null,
        'is_archived' => false,
        'completed_at' => null,
        'task_list_id' => null,
    ], $attributes));
}

test('the form offers the own task lists', function () {
    $user = User::factory()->create();
    $mine = taskListFor($user);
    $checklist = TaskList::factory()->checklist()->create(['user_id' => $user->id]);
    $foreign = taskListFor(User::factory()->create(), 'Fremd');

    $lists = Livewire::actingAs($user)->test(TaskManager::class)->viewData('assignableLists');

    expect($lists->pluck('id'))
        ->toContain($mine->id)
        ->not->toContain($checklist->id)
        ->not->toContain($foreign->id);
});

test('editing a task prefills its list', function () {
    $user = User::factory()->create();
    $list = taskListFor($user);
    $task = plainTask($user, ['task_list_id' => $list->id]);

    Livewire::actingAs($user)->test(TaskManager::class)
        ->call('editTask', $task->id)
        ->assertSet('task_list_id', $list->id);
});

test('a task without a list starts with an empty selection', function () {
    $user = User::factory()->create();
    $task = plainTask($user);

    Livewire::actingAs($user)->test(TaskManager::class)
        ->call('editTask', $task->id)
        ->assertSet('task_list_id', '');
});

test('saving assigns the chosen list to an existing task', function () {
    $user = User::factory()->create();
    $list = taskListFor($user);
    $task = plainTask($user);

    Livewire::actingAs($user)->test(TaskManager::class)
        ->call('editTask', $task->id)
        ->set('task_list_id', $list->id)
        ->call('updateTask');

    expect($task->fresh()->task_list_id)->toBe($list->id);
});

test('saving moves the task to another list', function () {
    $user = User::factory()->create();
    $from = taskListFor($user, 'Vorher');
    $to = taskListFor($user, 'Nachher');
    $task = plainTask($user, ['task_list_id' => $from->id]);

    Livewire::actingAs($user)->test(TaskManager::class)
        ->call('editTask', $task->id)
        ->set('task_list_id', $to->id)
        ->call('updateTask');

    expect($task->fresh()->task_list_id)->toBe($to->id);
});

test('an empty selection removes the task from its list', function () {
    $user = User::factory()->create();
    $list = taskListFor($user);
    $task = plainTask($user, ['task_list_id' => $list->id]);

    Livewire::actingAs($user)->test(TaskManager::class)
        ->call('editTask', $task->id)
        ->set('task_list_id', '')
        ->call('updateTask');

    expect($task->fresh()->task_list_id)->toBeNull();
});

test('a list owned by someone else is rejected', function () {
    $user = User::factory()->create();
    $foreign = taskListFor(User::factory()->create(), 'Fremd');
    $task = plainTask($user);

    Livewire::actingAs($user)->test(TaskManager::class)
        ->call('editTask', $task->id)
        ->set('task_list_id', $foreign->id)
        ->call('updateTask')
        ->assertHasErrors('task_list_id');

    expect($task->fresh()->task_list_id)->toBeNull();
});

test('a deleted list is rejected', function () {
    $user = User::factory()->create();
    $list = taskListFor($user);
    $list->delete();
    $task = plainTask($user);

    Livewire::actingAs($user)->test(TaskManager::class)
        ->call('editTask', $task->id)
        ->set('task_list_id', $list->id)
        ->call('updateTask')
        ->assertHasErrors('task_list_id');
});

test('a new task can be created inside a list', function () {
    $user = User::factory()->create();
    $list = taskListFor($user);

    Livewire::actingAs($user)->test(TaskManager::class)
        ->set('title', 'Neue Aufgabe')
        ->set('task_list_id', $list->id)
        ->call('createTask');

    expect(Task::where('title', 'Neue Aufgabe')->first()->task_list_id)->toBe($list->id);
});

test('the selection is cleared after saving', function () {
    $user = User::factory()->create();
    $list = taskListFor($user);

    Livewire::actingAs($user)->test(TaskManager::class)
        ->set('title', 'Neue Aufgabe')
        ->set('task_list_id', $list->id)
        ->call('createTask')
        ->assertSet('task_list_id', '');
});

test('a checklist the task already sits in stays selectable', function () {
    // Ueber die Schnittstelle kann eine Aufgabe in einer Checkliste landen.
    // Stuende die nicht zur Wahl, ginge die Zuordnung beim Speichern still
    // verloren.
    $user = User::factory()->create();
    $checklist = TaskList::factory()->checklist()->create(['user_id' => $user->id]);
    $task = plainTask($user, ['task_list_id' => $checklist->id]);

    $lists = Livewire::actingAs($user)->test(TaskManager::class)
        ->call('editTask', $task->id)
        ->viewData('assignableLists');

    expect($lists->pluck('id'))->toContain($checklist->id);
});

test('the list field is translated', function () {
    $user = User::factory()->create();
    taskListFor($user);

    Livewire::actingAs($user)->test(TaskManager::class)
        ->call('showCreateForm')
        ->assertSee('No list');

    app()->setLocale('de');

    Livewire::actingAs($user)->test(TaskManager::class)
        ->call('showCreateForm')
        ->assertSee('Keine Liste');
});

test('the field stays hidden without any list', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(TaskManager::class)
        ->call('showCreateForm')
        ->assertDontSee('No list');
});
