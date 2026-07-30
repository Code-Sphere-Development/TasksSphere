<?php

use App\Livewire\ListDetail;
use App\Models\ListItem;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function ownedChecklist(User $user): TaskList
{
    return TaskList::factory()->checklist()->create(['user_id' => $user->id]);
}

// --- mount / authorization ---

test('mount sets the task list on the component', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->assertSet('taskList.id', $list->id)
        ->assertSet('newItemTitle', '')
        ->assertSet('showCompletedItems', false)
        ->assertSet('showTaskPicker', false);
});

test('mount forbids viewing a task list owned by another user', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $list = ownedChecklist($owner);

    $this->actingAs($other);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->assertForbidden();
});

// --- render ---

test('render passes active and completed items for a checklist', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);

    $active = ListItem::factory()->create(['task_list_id' => $list->id, 'is_completed' => false]);
    $done = ListItem::factory()->completed()->create(['task_list_id' => $list->id]);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->assertViewHas('activeItems', fn ($items) => $items->contains('id', $active->id) && ! $items->contains('id', $done->id))
        ->assertViewHas('completedItems', fn ($items) => $items->contains('id', $done->id) && ! $items->contains('id', $active->id));
});

test('render passes assigned and available tasks for a tasks list', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->tasks()->create(['user_id' => $user->id]);

    $assigned = Task::factory()->create([
        'user_id' => $user->id,
        'task_list_id' => $list->id,
        'is_archived' => false,
    ]);
    $available = Task::factory()->create([
        'user_id' => $user->id,
        'task_list_id' => null,
        'is_archived' => false,
        'completed_at' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->assertViewHas('assignedTasks', fn ($tasks) => $tasks->contains('id', $assigned->id))
        ->assertViewHas('availableTasks', fn ($tasks) => $tasks->contains('id', $available->id) && ! $tasks->contains('id', $assigned->id));
});

// --- addItem ---

test('addItem creates a new item and clears the input', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->set('newItemTitle', 'Buy milk')
        ->call('addItem')
        ->assertSet('newItemTitle', '');

    $this->assertDatabaseHas('list_items', [
        'task_list_id' => $list->id,
        'title' => 'Buy milk',
        'position' => 0,
    ]);
});

test('addItem assigns an incremented position', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);
    ListItem::factory()->create(['task_list_id' => $list->id, 'position' => 5]);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->set('newItemTitle', 'Next item')
        ->call('addItem');

    $this->assertDatabaseHas('list_items', [
        'task_list_id' => $list->id,
        'title' => 'Next item',
        'position' => 6,
    ]);
});

test('addItem validates that a title is required', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->set('newItemTitle', '')
        ->call('addItem')
        ->assertHasErrors(['newItemTitle' => 'required']);

    expect($list->items()->count())->toBe(0);
});

// --- toggleItem ---

test('toggleItem flips the completion state', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);
    $item = ListItem::factory()->create(['task_list_id' => $list->id, 'is_completed' => false]);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->call('toggleItem', $item->id);

    expect($item->fresh()->is_completed)->toBeTrue();

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->call('toggleItem', $item->id);

    expect($item->fresh()->is_completed)->toBeFalse();
});

test('toggleItem rejects an item from a different list', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);
    $otherList = ownedChecklist($user);
    $foreignItem = ListItem::factory()->create(['task_list_id' => $otherList->id]);

    $this->actingAs($user);

    expect(fn () => Livewire::test(ListDetail::class, ['taskList' => $list])
        ->call('toggleItem', $foreignItem->id)
    )->toThrow(ModelNotFoundException::class);
});

// --- editing ---

test('startEditItem loads the item title and note into edit state', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);
    $item = ListItem::factory()->create([
        'task_list_id' => $list->id,
        'title' => 'Original',
        'note' => 'A note',
    ]);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->call('startEditItem', $item->id)
        ->assertSet('editingItemId', $item->id)
        ->assertSet('editingItemTitle', 'Original')
        ->assertSet('editingItemNote', 'A note');
});

test('startEditItem uses an empty string when the note is null', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);
    $item = ListItem::factory()->create(['task_list_id' => $list->id, 'note' => null]);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->call('startEditItem', $item->id)
        ->assertSet('editingItemNote', '');
});

test('saveEditItem updates the item and resets edit state', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);
    $item = ListItem::factory()->create(['task_list_id' => $list->id]);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->call('startEditItem', $item->id)
        ->set('editingItemTitle', 'Updated title')
        ->set('editingItemNote', 'Updated note')
        ->call('saveEditItem')
        ->assertSet('editingItemId', null)
        ->assertSet('editingItemTitle', '')
        ->assertSet('editingItemNote', '');

    $this->assertDatabaseHas('list_items', [
        'id' => $item->id,
        'title' => 'Updated title',
        'note' => 'Updated note',
    ]);
});

test('saveEditItem stores a null note when left empty', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);
    $item = ListItem::factory()->create(['task_list_id' => $list->id, 'note' => 'old']);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->call('startEditItem', $item->id)
        ->set('editingItemTitle', 'Keep title')
        ->set('editingItemNote', '')
        ->call('saveEditItem');

    $this->assertDatabaseHas('list_items', [
        'id' => $item->id,
        'note' => null,
    ]);
});

test('saveEditItem validates that the edited title is required', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);
    $item = ListItem::factory()->create(['task_list_id' => $list->id, 'title' => 'Keep me']);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->call('startEditItem', $item->id)
        ->set('editingItemTitle', '')
        ->call('saveEditItem')
        ->assertHasErrors(['editingItemTitle' => 'required']);

    expect($item->fresh()->title)->toBe('Keep me');
});

test('cancelEditItem resets the edit state', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);
    $item = ListItem::factory()->create(['task_list_id' => $list->id]);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->call('startEditItem', $item->id)
        ->call('cancelEditItem')
        ->assertSet('editingItemId', null)
        ->assertSet('editingItemTitle', '')
        ->assertSet('editingItemNote', '');
});

// --- deleteItem / clearCompleted ---

test('deleteItem removes the item', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);
    $item = ListItem::factory()->create(['task_list_id' => $list->id]);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->call('deleteItem', $item->id);

    $this->assertDatabaseMissing('list_items', ['id' => $item->id]);
});

test('clearCompleted deletes only completed items', function () {
    $user = User::factory()->create();
    $list = ownedChecklist($user);
    $active = ListItem::factory()->create(['task_list_id' => $list->id, 'is_completed' => false]);
    $done = ListItem::factory()->completed()->create(['task_list_id' => $list->id]);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->call('clearCompleted');

    $this->assertDatabaseHas('list_items', ['id' => $active->id]);
    $this->assertDatabaseMissing('list_items', ['id' => $done->id]);
});

// --- assignTask / removeTask ---

test('assignTask attaches the task to the list and closes the picker', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->tasks()->create(['user_id' => $user->id]);
    $task = Task::factory()->create(['user_id' => $user->id, 'task_list_id' => null]);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->set('showTaskPicker', true)
        ->call('assignTask', $task->id)
        ->assertSet('showTaskPicker', false);

    expect($task->fresh()->task_list_id)->toBe($list->id);
});

test('assignTask rejects a task owned by another user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $list = TaskList::factory()->tasks()->create(['user_id' => $user->id]);
    $foreignTask = Task::factory()->create(['user_id' => $other->id, 'task_list_id' => null]);

    $this->actingAs($user);

    expect(fn () => Livewire::test(ListDetail::class, ['taskList' => $list])
        ->call('assignTask', $foreignTask->id)
    )->toThrow(ModelNotFoundException::class);
});

test('removeTask detaches the task from the list', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->tasks()->create(['user_id' => $user->id]);
    $task = Task::factory()->create(['user_id' => $user->id, 'task_list_id' => $list->id]);

    $this->actingAs($user);

    Livewire::test(ListDetail::class, ['taskList' => $list])
        ->call('removeTask', $task->id);

    expect($task->fresh()->task_list_id)->toBeNull();
});
