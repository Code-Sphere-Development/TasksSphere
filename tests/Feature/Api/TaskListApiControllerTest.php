<?php

use App\Models\ListItem;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| index()  GET /api/task-lists
|--------------------------------------------------------------------------
*/

test('index requires authentication', function () {
    $this->getJson('/api/task-lists')->assertUnauthorized();
});

test('index returns only the authenticated users own lists', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $mine = TaskList::factory()->for($user)->create();
    TaskList::factory()->for($other)->create();

    Sanctum::actingAs($user);

    $response = $this->getJson('/api/task-lists')->assertOk();

    $response->assertJsonCount(1);
    $response->assertJsonFragment(['id' => $mine->id]);
});

test('index orders lists by position', function () {
    $user = User::factory()->create();

    $second = TaskList::factory()->for($user)->create(['position' => 2]);
    $first = TaskList::factory()->for($user)->create(['position' => 1]);

    Sanctum::actingAs($user);

    $ids = collect($this->getJson('/api/task-lists')->assertOk()->json())
        ->pluck('id')
        ->all();

    expect($ids)->toBe([$first->id, $second->id]);
});

/*
|--------------------------------------------------------------------------
| store()  POST /api/task-lists
|--------------------------------------------------------------------------
*/

test('store requires authentication', function () {
    $this->postJson('/api/task-lists', ['title' => 'x', 'type' => 'tasks'])
        ->assertUnauthorized();
});

test('store creates a personal checklist for the authenticated user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/task-lists', [
        'title' => 'Groceries',
        'type' => 'checklist',
        'description' => 'Weekly shopping',
        'icon' => 'cart',
        'color' => '#ffffff',
    ])->assertCreated();

    $response->assertJsonFragment([
        'title' => 'Groceries',
        'type' => 'checklist',
        'user_id' => $user->id,
        'team_id' => null,
    ]);

    $this->assertDatabaseHas('task_lists', [
        'title' => 'Groceries',
        'user_id' => $user->id,
        'team_id' => null,
    ]);
});

test('store creates a tasks list', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/task-lists', [
        'title' => 'Todo',
        'type' => 'tasks',
    ])->assertCreated()
        ->assertJsonFragment(['type' => 'tasks', 'user_id' => $user->id]);
});

test('store fails validation when title is missing', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/task-lists', ['type' => 'tasks'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('title');
});

test('store fails validation when type is missing', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/task-lists', ['title' => 'x'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('type');
});

test('store fails validation when type is not tasks or checklist', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/task-lists', ['title' => 'x', 'type' => 'invalid'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('type');
});

test('store fails validation when title exceeds 255 characters', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/task-lists', ['title' => str_repeat('a', 256), 'type' => 'tasks'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('title');
});

// Team support is not wired up, so a team-scoped list is rejected with a
// 422 validation error (team_id is prohibited) instead of crashing.
test('store rejects a team_id because teams are not supported', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/task-lists', [
        'title' => 'Team list',
        'type' => 'tasks',
        'team_id' => 1,
    ])->assertStatus(422)->assertJsonValidationErrors('team_id');
});

/*
|--------------------------------------------------------------------------
| show()  GET /api/task-lists/{taskList}
|--------------------------------------------------------------------------
*/

test('show requires authentication', function () {
    $list = TaskList::factory()->create();

    $this->getJson("/api/task-lists/{$list->id}")->assertUnauthorized();
});

test('show returns an owned checklist with its items loaded', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->for($user)->checklist()->create();
    $item = ListItem::factory()->for($list, 'taskList')->create();

    Sanctum::actingAs($user);

    $this->getJson("/api/task-lists/{$list->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $list->id])
        ->assertJsonPath('items.0.id', $item->id);
});

test('show returns an owned tasks list with the tasks relation loaded', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->for($user)->tasks()->create();

    Sanctum::actingAs($user);

    $this->getJson("/api/task-lists/{$list->id}")
        ->assertOk()
        ->assertJsonFragment(['id' => $list->id])
        ->assertJsonPath('tasks', []);
});

test('show forbids viewing another users list', function () {
    $owner = User::factory()->create();
    $list = TaskList::factory()->for($owner)->create();

    Sanctum::actingAs(User::factory()->create());

    $this->getJson("/api/task-lists/{$list->id}")->assertForbidden();
});

test('show returns 404 for a non-existent list', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->getJson('/api/task-lists/999999')->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| update()  PUT /api/task-lists/{taskList}
|--------------------------------------------------------------------------
*/

test('update requires authentication', function () {
    $list = TaskList::factory()->create();

    $this->putJson("/api/task-lists/{$list->id}", ['title' => 'x'])
        ->assertUnauthorized();
});

test('update modifies an owned list', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->for($user)->create(['title' => 'Old', 'position' => 0]);

    Sanctum::actingAs($user);

    $this->putJson("/api/task-lists/{$list->id}", [
        'title' => 'New title',
        'position' => 5,
    ])->assertOk()
        ->assertJsonFragment(['title' => 'New title', 'position' => 5]);

    $this->assertDatabaseHas('task_lists', [
        'id' => $list->id,
        'title' => 'New title',
        'position' => 5,
    ]);
});

test('update forbids modifying another users list', function () {
    $owner = User::factory()->create();
    $list = TaskList::factory()->for($owner)->create(['title' => 'Owned']);

    Sanctum::actingAs(User::factory()->create());

    $this->putJson("/api/task-lists/{$list->id}", ['title' => 'Hacked'])
        ->assertForbidden();

    $this->assertDatabaseHas('task_lists', ['id' => $list->id, 'title' => 'Owned']);
});

test('update fails validation when title exceeds 255 characters', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->putJson("/api/task-lists/{$list->id}", ['title' => str_repeat('a', 256)])
        ->assertStatus(422)
        ->assertJsonValidationErrors('title');
});

test('update fails validation when position is negative', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->putJson("/api/task-lists/{$list->id}", ['position' => -1])
        ->assertStatus(422)
        ->assertJsonValidationErrors('position');
});

test('update returns 404 for a non-existent list', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->putJson('/api/task-lists/999999', ['title' => 'x'])->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| destroy()  DELETE /api/task-lists/{taskList}
|--------------------------------------------------------------------------
*/

test('destroy requires authentication', function () {
    $list = TaskList::factory()->create();

    $this->deleteJson("/api/task-lists/{$list->id}")->assertUnauthorized();
});

test('destroy soft-deletes an owned list', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->for($user)->create();

    Sanctum::actingAs($user);

    $this->deleteJson("/api/task-lists/{$list->id}")
        ->assertOk()
        ->assertJson(['message' => 'List deleted']);

    $this->assertSoftDeleted('task_lists', ['id' => $list->id]);
});

test('destroy forbids deleting another users list', function () {
    $owner = User::factory()->create();
    $list = TaskList::factory()->for($owner)->create();

    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson("/api/task-lists/{$list->id}")->assertForbidden();

    $this->assertDatabaseHas('task_lists', ['id' => $list->id, 'deleted_at' => null]);
});

test('destroy returns 404 for a non-existent list', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->deleteJson('/api/task-lists/999999')->assertNotFound();
});
