<?php

use App\Models\ListItem;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| index — GET /api/task-lists/{taskList}/items
|--------------------------------------------------------------------------
*/

test('index returns the owner list items ordered by position', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($user)->create();

    ListItem::factory()->create(['task_list_id' => $list->id, 'title' => 'second', 'position' => 2]);
    ListItem::factory()->create(['task_list_id' => $list->id, 'title' => 'first', 'position' => 1]);

    Sanctum::actingAs($user);

    $response = $this->getJson("/api/task-lists/{$list->id}/items");

    $response->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.title', 'first')
        ->assertJsonPath('1.title', 'second');
});

test('index requires authentication', function () {
    $list = TaskList::factory()->checklist()->create();

    $this->getJson("/api/task-lists/{$list->id}/items")
        ->assertUnauthorized();
});

test('index forbids a user who does not own the parent list', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($owner)->create();

    Sanctum::actingAs($other);

    $this->getJson("/api/task-lists/{$list->id}/items")
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| store — POST /api/task-lists/{taskList}/items
|--------------------------------------------------------------------------
*/

test('store creates an item on a checklist and returns 201', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($user)->create();

    Sanctum::actingAs($user);

    $response = $this->postJson("/api/task-lists/{$list->id}/items", [
        'title' => 'Buy milk',
        'note' => 'From the store',
    ]);

    $response->assertCreated()
        ->assertJsonPath('title', 'Buy milk')
        ->assertJsonPath('note', 'From the store')
        ->assertJsonPath('position', 0);

    $this->assertDatabaseHas('list_items', [
        'task_list_id' => $list->id,
        'title' => 'Buy milk',
        'position' => 0,
    ]);
});

test('store assigns the next position after the current maximum', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($user)->create();
    ListItem::factory()->create(['task_list_id' => $list->id, 'position' => 5]);

    Sanctum::actingAs($user);

    $this->postJson("/api/task-lists/{$list->id}/items", ['title' => 'Next'])
        ->assertCreated()
        ->assertJsonPath('position', 6);
});

test('store rejects adding items to a non-checklist list with 422', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->tasks()->for($user)->create();

    Sanctum::actingAs($user);

    $this->postJson("/api/task-lists/{$list->id}/items", ['title' => 'Nope'])
        ->assertStatus(422);

    $this->assertDatabaseMissing('list_items', ['task_list_id' => $list->id]);
});

test('store validates that a title is required', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($user)->create();

    Sanctum::actingAs($user);

    $this->postJson("/api/task-lists/{$list->id}/items", ['note' => 'no title'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('title');
});

test('store requires authentication', function () {
    $list = TaskList::factory()->checklist()->create();

    $this->postJson("/api/task-lists/{$list->id}/items", ['title' => 'x'])
        ->assertUnauthorized();
});

test('store forbids a user who does not own the parent list', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($owner)->create();

    Sanctum::actingAs($other);

    $this->postJson("/api/task-lists/{$list->id}/items", ['title' => 'x'])
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| update — PUT /api/task-lists/{taskList}/items/{item}
|--------------------------------------------------------------------------
*/

test('update modifies an item belonging to the list', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($user)->create();
    $item = ListItem::factory()->create(['task_list_id' => $list->id, 'title' => 'old']);

    Sanctum::actingAs($user);

    $this->putJson("/api/task-lists/{$list->id}/items/{$item->id}", [
        'title' => 'new title',
        'position' => 3,
    ])
        ->assertOk()
        ->assertJsonPath('title', 'new title')
        ->assertJsonPath('position', 3);

    $this->assertDatabaseHas('list_items', [
        'id' => $item->id,
        'title' => 'new title',
        'position' => 3,
    ]);
});

test('update can toggle the completion state', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($user)->create();
    $item = ListItem::factory()->create(['task_list_id' => $list->id, 'is_completed' => false]);

    Sanctum::actingAs($user);

    $this->putJson("/api/task-lists/{$list->id}/items/{$item->id}", [
        'is_completed' => true,
    ])
        ->assertOk()
        ->assertJsonPath('is_completed', true);

    $this->assertDatabaseHas('list_items', [
        'id' => $item->id,
        'is_completed' => true,
    ]);
});

test('update returns 404 when the item does not belong to the list', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($user)->create();
    $otherList = TaskList::factory()->checklist()->for($user)->create();
    $item = ListItem::factory()->create(['task_list_id' => $otherList->id]);

    Sanctum::actingAs($user);

    $this->putJson("/api/task-lists/{$list->id}/items/{$item->id}", ['title' => 'x'])
        ->assertNotFound();
});

test('update validates the provided fields', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($user)->create();
    $item = ListItem::factory()->create(['task_list_id' => $list->id]);

    Sanctum::actingAs($user);

    $this->putJson("/api/task-lists/{$list->id}/items/{$item->id}", [
        'is_completed' => 'not-a-boolean',
        'position' => -5,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['is_completed', 'position']);
});

test('update requires authentication', function () {
    $list = TaskList::factory()->checklist()->create();
    $item = ListItem::factory()->create(['task_list_id' => $list->id]);

    $this->putJson("/api/task-lists/{$list->id}/items/{$item->id}", ['title' => 'x'])
        ->assertUnauthorized();
});

test('update forbids a user who does not own the parent list', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($owner)->create();
    $item = ListItem::factory()->create(['task_list_id' => $list->id]);

    Sanctum::actingAs($other);

    $this->putJson("/api/task-lists/{$list->id}/items/{$item->id}", ['title' => 'x'])
        ->assertForbidden();
});

/*
|--------------------------------------------------------------------------
| destroy — DELETE /api/task-lists/{taskList}/items/{item}
|--------------------------------------------------------------------------
*/

test('destroy deletes an item belonging to the list', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($user)->create();
    $item = ListItem::factory()->create(['task_list_id' => $list->id]);

    Sanctum::actingAs($user);

    $this->deleteJson("/api/task-lists/{$list->id}/items/{$item->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Item deleted');

    $this->assertDatabaseMissing('list_items', ['id' => $item->id]);
});

test('destroy returns 404 when the item does not belong to the list', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($user)->create();
    $otherList = TaskList::factory()->checklist()->for($user)->create();
    $item = ListItem::factory()->create(['task_list_id' => $otherList->id]);

    Sanctum::actingAs($user);

    $this->deleteJson("/api/task-lists/{$list->id}/items/{$item->id}")
        ->assertNotFound();

    $this->assertDatabaseHas('list_items', ['id' => $item->id]);
});

test('destroy requires authentication', function () {
    $list = TaskList::factory()->checklist()->create();
    $item = ListItem::factory()->create(['task_list_id' => $list->id]);

    $this->deleteJson("/api/task-lists/{$list->id}/items/{$item->id}")
        ->assertUnauthorized();
});

test('destroy forbids a user who does not own the parent list', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $list = TaskList::factory()->checklist()->for($owner)->create();
    $item = ListItem::factory()->create(['task_list_id' => $list->id]);

    Sanctum::actingAs($other);

    $this->deleteJson("/api/task-lists/{$list->id}/items/{$item->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('list_items', ['id' => $item->id]);
});
