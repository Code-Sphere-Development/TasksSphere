<?php

use App\Models\Household;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a task has no assignee by default', function () {
    $task = Task::factory()->create();

    expect($task->assigned_to)->toBeNull();
    expect($task->assignees)->toBeEmpty();
});

test('assigning a task sets the responsible person', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    Household::createFor($owner, 'Familie')->addMember($member);
    $task = Task::factory()->for($owner)->create();

    $task->assignTo($member, $owner);

    expect($task->fresh()->assigned_to)->toBe($member->id);
});

test('assigning a task adds the person to the pool', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $task = Task::factory()->for($owner)->create();

    $task->assignTo($member, $owner);

    expect($task->fresh()->assignees->pluck('id'))->toContain($member->id);
});

test('the pool records who assigned the person', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $task = Task::factory()->for($owner)->create();

    $task->assignTo($member, $owner);

    expect($task->fresh()->assignees->first()->pivot->assigned_by)->toBe($owner->id);
});

test('assigning the same person twice does not duplicate the pool entry', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $task = Task::factory()->for($owner)->create();

    $task->assignTo($member, $owner);
    $task->assignTo($member, $owner);

    expect($task->fresh()->assignees)->toHaveCount(1);
});

test('a person can be removed from the pool', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $task = Task::factory()->for($owner)->create();
    $task->assignTo($member, $owner);

    $task->removeAssignee($member);

    expect($task->fresh()->assignees)->toBeEmpty();
});

test('removing the responsible person clears the assignment', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $task = Task::factory()->for($owner)->create();
    $task->assignTo($member, $owner);

    $task->removeAssignee($member);

    expect($task->fresh()->assigned_to)->toBeNull();
});

test('deleting the assigned user leaves the task without a responsible person', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $task = Task::factory()->for($owner)->create();
    $task->assignTo($member, $owner);

    $member->delete();

    expect($task->fresh()->assigned_to)->toBeNull();
});

test('forPerson finds tasks the user owns', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    expect(Task::forPerson($user->id)->pluck('id'))->toContain($task->id);
});

test('forPerson finds tasks assigned to the user', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $task = Task::factory()->for($owner)->create();
    $task->assignTo($member, $owner);

    expect(Task::forPerson($member->id)->pluck('id'))->toContain($task->id);
});

test('forPerson ignores tasks of other people', function () {
    $user = User::factory()->create();
    $foreign = Task::factory()->create();

    expect(Task::forPerson($user->id)->pluck('id'))->not->toContain($foreign->id);
});
