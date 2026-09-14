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
    $this->list = TaskList::factory()->create([
        'user_id' => $this->owner->id,
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

test('view allows the owner', function () {
    expect($this->policy->view($this->owner, $this->list))->toBeTrue();
});

test('view denies a non-owner', function () {
    expect($this->policy->view($this->other, $this->list))->toBeFalse();
});

test('update allows the owner', function () {
    expect($this->policy->update($this->owner, $this->list))->toBeTrue();
});

test('update denies a non-owner', function () {
    expect($this->policy->update($this->other, $this->list))->toBeFalse();
});

test('delete allows the owner', function () {
    expect($this->policy->delete($this->owner, $this->list))->toBeTrue();
});

test('delete denies a non-owner', function () {
    expect($this->policy->delete($this->other, $this->list))->toBeFalse();
});
