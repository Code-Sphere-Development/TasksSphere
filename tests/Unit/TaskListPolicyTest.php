<?php

use App\Models\TaskList;
use App\Models\User;
use App\Policies\TaskListPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->policy = new TaskListPolicy;
    $this->owner = User::factory()->create();
    $this->other = User::factory()->create();
    // Personal list (team_id = null) — the only branch that works,
    // since App\Models\Team does not exist in this codebase.
    $this->list = TaskList::factory()->create([
        'user_id' => $this->owner->id,
        'team_id' => null,
    ]);
});

test('viewAny always allows any user', function () {
    expect($this->policy->viewAny($this->owner))->toBeTrue();
    expect($this->policy->viewAny($this->other))->toBeTrue();
});

test('create always allows any user', function () {
    expect($this->policy->create($this->owner))->toBeTrue();
    expect($this->policy->create($this->other))->toBeTrue();
});

test('view allows the owner of a personal list', function () {
    expect($this->policy->view($this->owner, $this->list))->toBeTrue();
});

test('view denies a non-owner of a personal list', function () {
    expect($this->policy->view($this->other, $this->list))->toBeFalse();
});

test('update allows the owner of a personal list', function () {
    expect($this->policy->update($this->owner, $this->list))->toBeTrue();
});

test('update denies a non-owner of a personal list', function () {
    expect($this->policy->update($this->other, $this->list))->toBeFalse();
});

test('delete allows the owner of a personal list', function () {
    expect($this->policy->delete($this->owner, $this->list))->toBeTrue();
});

test('delete denies a non-owner of a personal list', function () {
    expect($this->policy->delete($this->other, $this->list))->toBeFalse();
});

// --- Owner short-circuit still works even when a team is attached ---
// For the owner, the user_id check returns true before the (broken)
// team branch is ever reached.

test('view allows the owner even when a team is attached', function () {
    $teamList = TaskList::factory()->create([
        'user_id' => $this->owner->id,
        'team_id' => 999,
    ]);

    expect($this->policy->view($this->owner, $teamList))->toBeTrue();
});

test('delete allows the owner even when a team is attached', function () {
    $teamList = TaskList::factory()->create([
        'user_id' => $this->owner->id,
        'team_id' => 999,
    ]);

    expect($this->policy->delete($this->owner, $teamList))->toBeTrue();
});

// --- Team support is not wired up ---
// TaskListPolicy no longer references the (missing) App\Models\Team; a
// team-scoped list safely denies non-owners instead of crashing.

test('view on a team list denies a non-owner', function () {
    $teamList = TaskList::factory()->create([
        'user_id' => $this->owner->id,
        'team_id' => 999,
    ]);

    expect($this->policy->view($this->other, $teamList))->toBeFalse();
});

test('delete on a team list denies a non-owner', function () {
    $teamList = TaskList::factory()->create([
        'user_id' => $this->owner->id,
        'team_id' => 999,
    ]);

    expect($this->policy->delete($this->other, $teamList))->toBeFalse();
});
