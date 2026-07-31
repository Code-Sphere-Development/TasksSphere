<?php

use App\Livewire\ListManager;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Mount / render
|--------------------------------------------------------------------------
*/

test('component mounts with default state', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->assertSet('title', '')
        ->assertSet('description', '')
        ->assertSet('type', 'checklist')
        ->assertSet('icon', '')
        ->assertSet('color', '')
        ->assertSet('showForm', false)
        ->assertSet('isEditing', false)
        ->assertSet('editingListId', null)
        ->assertStatus(200);
});

test('render only shows lists belonging to the current user', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $mine = TaskList::factory()->create(['user_id' => $user->id, 'team_id' => null]);
    $theirs = TaskList::factory()->create(['user_id' => $other->id, 'team_id' => null]);

    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->assertViewHas('myLists', fn ($lists) => $lists->contains('id', $mine->id)
            && ! $lists->contains('id', $theirs->id))
        ->assertViewHas('teamLists', fn ($lists) => $lists->isEmpty());
});

test('render orders my lists by position', function () {
    $user = User::factory()->create();

    $second = TaskList::factory()->create(['user_id' => $user->id, 'team_id' => null, 'position' => 5]);
    $first = TaskList::factory()->create(['user_id' => $user->id, 'team_id' => null, 'position' => 1]);

    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->assertViewHas('myLists', fn ($lists) => $lists->pluck('id')->toArray() === [$first->id, $second->id]);
});

/*
|--------------------------------------------------------------------------
| showCreateForm
|--------------------------------------------------------------------------
*/

test('showCreateForm resets the form and opens it', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->set('title', 'stale')
        ->set('isEditing', true)
        ->set('editingListId', 99)
        ->call('showCreateForm')
        ->assertSet('title', '')
        ->assertSet('type', 'checklist')
        ->assertSet('isEditing', false)
        ->assertSet('editingListId', null)
        ->assertSet('showForm', true);
});

/*
|--------------------------------------------------------------------------
| createList
|--------------------------------------------------------------------------
*/

test('createList persists a list for the current user and resets the form', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->set('title', 'Groceries')
        ->set('description', 'Weekly shopping')
        ->set('type', 'checklist')
        ->set('icon', 'cart')
        ->set('color', '#ff0000')
        ->set('showForm', true)
        ->call('createList')
        ->assertSet('title', '')
        ->assertSet('showForm', false)
        ->assertSet('isEditing', false);

    $this->assertDatabaseHas('task_lists', [
        'title' => 'Groceries',
        'description' => 'Weekly shopping',
        'type' => 'checklist',
        'icon' => 'cart',
        'color' => '#ff0000',
        'user_id' => $user->id,
        'team_id' => null,
    ]);
});

test('createList can create a tasks type list', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->set('title', 'Project')
        ->set('type', 'tasks')
        ->call('createList');

    $this->assertDatabaseHas('task_lists', [
        'title' => 'Project',
        'type' => 'tasks',
        'user_id' => $user->id,
    ]);
});

test('createList stores empty icon and color as null', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->set('title', 'No decorations')
        ->set('icon', '')
        ->set('color', '')
        ->call('createList');

    $list = TaskList::where('title', 'No decorations')->firstOrFail();

    expect($list->icon)->toBeNull();
    expect($list->color)->toBeNull();
});

test('createList fails validation when title is empty', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->set('title', '')
        ->call('createList')
        ->assertHasErrors(['title' => 'required']);

    expect(TaskList::count())->toBe(0);
});

test('createList fails validation for invalid type', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->set('title', 'Valid')
        ->set('type', 'bogus')
        ->call('createList')
        ->assertHasErrors(['type']);

    expect(TaskList::count())->toBe(0);
});

test('createList fails validation for a color longer than 7 characters', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->set('title', 'Valid')
        ->set('color', '#ff00000')
        ->call('createList')
        ->assertHasErrors(['color']);
});

/*
|--------------------------------------------------------------------------
| createTeamList
|--------------------------------------------------------------------------
| Team features are disabled in this app and the User model has no
| currentTeam relationship, so Auth::user()->currentTeam?->id resolves to
| null. createTeamList therefore creates an orphan list (team_id null and
| user_id unset). This asserts the current behavior; see report.
*/

test('createTeamList creates an orphan list when no current team exists', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->set('title', 'Team list')
        ->set('type', 'checklist')
        ->call('createTeamList')
        ->assertSet('title', '');

    $this->assertDatabaseHas('task_lists', [
        'title' => 'Team list',
        'team_id' => null,
        'user_id' => null,
    ]);
});

test('createTeamList still validates the title', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->set('title', '')
        ->call('createTeamList')
        ->assertHasErrors(['title' => 'required']);

    expect(TaskList::count())->toBe(0);
});

/*
|--------------------------------------------------------------------------
| editList
|--------------------------------------------------------------------------
*/

test('editList loads the list into the form', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->create([
        'user_id' => $user->id,
        'team_id' => null,
        'title' => 'Original',
        'description' => 'Original description',
        'type' => 'tasks',
        'icon' => 'star',
        'color' => '#00ff00',
    ]);

    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->call('editList', $list->id)
        ->assertSet('editingListId', $list->id)
        ->assertSet('title', 'Original')
        ->assertSet('description', 'Original description')
        ->assertSet('type', 'tasks')
        ->assertSet('icon', 'star')
        ->assertSet('color', '#00ff00')
        ->assertSet('isEditing', true)
        ->assertSet('showForm', true);
});

test('editList coalesces null icon and color to empty strings', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->create([
        'user_id' => $user->id,
        'team_id' => null,
        'icon' => null,
        'color' => null,
    ]);

    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->call('editList', $list->id)
        ->assertSet('icon', '')
        ->assertSet('color', '');
});

test('editList throws when the list does not exist', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(fn () => Livewire::test(ListManager::class)->call('editList', 999999))
        ->toThrow(ModelNotFoundException::class);
});

test('editList is denied for another users list', function () {
    // The policy denies the action; Livewire renders the AuthorizationException
    // as a 403 response (it is not re-thrown in test mode), so we assert the
    // list was never loaded into the component.
    $user = User::factory()->create();
    $other = User::factory()->create();
    $list = TaskList::factory()->create(['user_id' => $other->id, 'team_id' => null]);

    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->call('editList', $list->id)
        ->assertSet('editingListId', null)
        ->assertSet('isEditing', false)
        ->assertSet('title', '');
});

/*
|--------------------------------------------------------------------------
| updateList
|--------------------------------------------------------------------------
*/

test('updateList updates the editable fields and resets the form', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->create([
        'user_id' => $user->id,
        'team_id' => null,
        'title' => 'Old',
        'description' => 'Old desc',
        'icon' => 'old',
        'color' => '#111111',
    ]);

    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->call('editList', $list->id)
        ->set('title', 'New title')
        ->set('description', 'New desc')
        ->set('icon', 'new')
        ->set('color', '#222222')
        ->call('updateList')
        ->assertSet('showForm', false)
        ->assertSet('isEditing', false)
        ->assertSet('editingListId', null);

    $this->assertDatabaseHas('task_lists', [
        'id' => $list->id,
        'title' => 'New title',
        'description' => 'New desc',
        'icon' => 'new',
        'color' => '#222222',
    ]);
});

test('updateList changes the list type', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->create([
        'user_id' => $user->id,
        'team_id' => null,
        'type' => 'checklist',
    ]);

    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->set('editingListId', $list->id)
        ->set('title', 'Renamed')
        ->set('type', 'tasks')
        ->call('updateList');

    expect($list->fresh()->type)->toBe('tasks');
});

test('updateList stores empty icon and color as null', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->create([
        'user_id' => $user->id,
        'team_id' => null,
        'icon' => 'x',
        'color' => '#abcdef',
    ]);

    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->set('editingListId', $list->id)
        ->set('title', 'Cleared')
        ->set('icon', '')
        ->set('color', '')
        ->call('updateList');

    $fresh = $list->fresh();
    expect($fresh->icon)->toBeNull();
    expect($fresh->color)->toBeNull();
});

test('updateList fails validation when title is empty', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->create([
        'user_id' => $user->id,
        'team_id' => null,
        'title' => 'Keep me',
    ]);

    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->set('editingListId', $list->id)
        ->set('title', '')
        ->call('updateList')
        ->assertHasErrors(['title' => 'required']);

    expect($list->fresh()->title)->toBe('Keep me');
});

test('updateList is denied for another users list', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $list = TaskList::factory()->create([
        'user_id' => $other->id,
        'team_id' => null,
        'title' => 'Not mine',
    ]);

    $this->actingAs($user);

    // Denied by policy; Livewire renders the 403 rather than re-throwing, so we
    // assert the record was not modified.
    Livewire::test(ListManager::class)
        ->set('editingListId', $list->id)
        ->set('title', 'Hacked')
        ->call('updateList');

    expect($list->fresh()->title)->toBe('Not mine');
});

/*
|--------------------------------------------------------------------------
| deleteList
|--------------------------------------------------------------------------
*/

test('deleteList soft deletes the users own list', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->create(['user_id' => $user->id, 'team_id' => null]);

    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->call('deleteList', $list->id);

    $this->assertSoftDeleted('task_lists', ['id' => $list->id]);
});

test('deleteList throws when the list does not exist', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    expect(fn () => Livewire::test(ListManager::class)->call('deleteList', 999999))
        ->toThrow(ModelNotFoundException::class);
});

test('deleteList is denied for another users list', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();
    $list = TaskList::factory()->create(['user_id' => $other->id, 'team_id' => null]);

    $this->actingAs($user);

    // Denied by policy; Livewire renders the 403 rather than re-throwing, so we
    // assert the list was not soft deleted.
    Livewire::test(ListManager::class)->call('deleteList', $list->id);

    $this->assertDatabaseHas('task_lists', ['id' => $list->id, 'deleted_at' => null]);
});

/*
|--------------------------------------------------------------------------
| resetForm
|--------------------------------------------------------------------------
*/

test('resetForm restores all form properties to defaults', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(ListManager::class)
        ->set('title', 'x')
        ->set('description', 'y')
        ->set('type', 'tasks')
        ->set('icon', 'i')
        ->set('color', '#000000')
        ->set('showForm', true)
        ->set('isEditing', true)
        ->set('editingListId', 5)
        ->call('resetForm')
        ->assertSet('title', '')
        ->assertSet('description', '')
        ->assertSet('type', 'checklist')
        ->assertSet('icon', '')
        ->assertSet('color', '')
        ->assertSet('showForm', false)
        ->assertSet('isEditing', false)
        ->assertSet('editingListId', null);
});
