<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can have tasks', function () {
    $user = User::factory()->create();

    $task = $user->tasks()->create([
        'title' => 'Test Task',
        'description' => 'Description',
    ]);

    $this->assertCount(1, $user->tasks);
    $this->assertEquals('Test Task', $user->tasks->first()->title);
    $this->assertEquals($user->id, $task->user_id);
});

test('task belongs to user', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id]);

    $this->assertInstanceOf(User::class, $task->user);
    $this->assertEquals($user->id, $task->user->id);
});
