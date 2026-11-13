<?php

use App\Livewire\ListDetail;
use App\Livewire\TaskManager;
use App\Models\Task;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the task list shows the id of each task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Muell rausbringen',
        'due_at' => now()->addHour(),
        'recurrence_rule' => null,
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->assertSee('Muell rausbringen')
        ->assertSeeHtml('title="Aufgaben-ID">#'.$task->id.'</span>');
});

test('the task detail shows the id', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'recurrence_rule' => null,
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->call('showTaskDetail', $task->id)
        ->assertSeeHtml('#'.$task->id.'</dd>');
});

test('a tasks list shows the id of its tasks', function () {
    $user = User::factory()->create();
    $list = TaskList::factory()->tasks()->create(['user_id' => $user->id]);
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'task_list_id' => $list->id,
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(ListDetail::class, ['taskList' => $list])
        ->assertSeeHtml('title="Aufgaben-ID">#'.$task->id.'</span>');
});
