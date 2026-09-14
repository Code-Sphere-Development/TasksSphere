<?php

use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new TaskPolicy;
    $this->owner = User::factory()->create();
    $this->other = User::factory()->create();
    $this->task = Task::factory()->create(['user_id' => $this->owner->id]);
});

test('viewAny always allows any user', function () {
    expect($this->policy->viewAny($this->owner))->toBeTrue();
    expect($this->policy->viewAny($this->other))->toBeTrue();
});

test('create always allows any user', function () {
    expect($this->policy->create($this->owner))->toBeTrue();
    expect($this->policy->create($this->other))->toBeTrue();
});

test('view allows the owner', function () {
    expect($this->policy->view($this->owner, $this->task))->toBeTrue();
});

test('view denies a non-owner', function () {
    expect($this->policy->view($this->other, $this->task))->toBeFalse();
});

test('update allows the owner', function () {
    expect($this->policy->update($this->owner, $this->task))->toBeTrue();
});

test('update denies a non-owner', function () {
    expect($this->policy->update($this->other, $this->task))->toBeFalse();
});

test('delete allows the owner', function () {
    expect($this->policy->delete($this->owner, $this->task))->toBeTrue();
});

test('delete denies a non-owner', function () {
    expect($this->policy->delete($this->other, $this->task))->toBeFalse();
});

test('restore denies everyone including the owner', function () {
    expect($this->policy->restore($this->owner, $this->task))->toBeFalse();
    expect($this->policy->restore($this->other, $this->task))->toBeFalse();
});

test('forceDelete denies everyone including the owner', function () {
    expect($this->policy->forceDelete($this->owner, $this->task))->toBeFalse();
    expect($this->policy->forceDelete($this->other, $this->task))->toBeFalse();
});

// --- Zuweisung ---------------------------------------------------------------
// Wer zustaendig ist, muss die Aufgabe sehen und bearbeiten koennen. Loeschen
// bleibt beim Besitzer: Zustaendige erledigen, sie vernichten nicht.

test('view allows the assigned person', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $task = Task::factory()->for($owner)->create();
    $task->assignTo($member, $owner);

    expect((new TaskPolicy)->view($member, $task->fresh()))->toBeTrue();
});

test('update allows the assigned person', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $task = Task::factory()->for($owner)->create();
    $task->assignTo($member, $owner);

    expect((new TaskPolicy)->update($member, $task->fresh()))->toBeTrue();
});

test('delete denies the assigned person', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $task = Task::factory()->for($owner)->create();
    $task->assignTo($member, $owner);

    expect((new TaskPolicy)->delete($member, $task->fresh()))->toBeFalse();
});

test('view denies someone who is merely in the pool but not responsible', function () {
    $owner = User::factory()->create();
    $inPool = User::factory()->create();
    $task = Task::factory()->for($owner)->create();
    $task->assignees()->attach($inPool->id, ['assigned_by' => $owner->id]);

    expect((new TaskPolicy)->view($inPool, $task->fresh()))->toBeFalse();
});

test('view still denies an unrelated user', function () {
    $stranger = User::factory()->create();
    $task = Task::factory()->create();

    expect((new TaskPolicy)->view($stranger, $task))->toBeFalse();
});
