<?php

use App\Livewire\TaskManager;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Einordnung der Termine
|--------------------------------------------------------------------------
| Haelt die Regeln fest, nach denen das Dashboard Termine einsortiert. Sie
| steckten bis hierher ungetestet in einem @php-Block im Blade.
*/

function dashboardFor(User $user): array
{
    return Livewire::actingAs($user)->test(TaskManager::class)->viewData('groups');
}

function taskAt(User $user, string $title, ?Carbon\Carbon $due): Task
{
    return Task::factory()->for($user)->create([
        'title' => $title,
        'due_at' => $due,
        'is_archived' => false,
        'completed_at' => null,
        'recurrence_rule' => null,
        'recurrence_timezone' => null,
    ]);
}

test('a task from yesterday counts as overdue', function () {
    $user = User::factory()->create();
    $task = taskAt($user, 'Gestern', now()->subDay()->setTime(9, 0));

    expect(dashboardFor($user)['overdue']->pluck('task.id'))->toContain($task->id);
});

test('a task earlier today counts as today, not overdue', function () {
    $user = User::factory()->create();
    $task = taskAt($user, 'Heute frueh', now()->startOfDay()->addHours(2));

    $groups = dashboardFor($user);

    expect($groups['today']->pluck('task.id'))->toContain($task->id);
    expect($groups['overdue']->pluck('task.id'))->not->toContain($task->id);
});

test('a task later today counts as today', function () {
    $user = User::factory()->create();
    $task = taskAt($user, 'Heute spaet', now()->endOfDay()->subHour());

    expect(dashboardFor($user)['today']->pluck('task.id'))->toContain($task->id);
});

test('a task tomorrow lands in the first upcoming day', function () {
    app()->setLocale('de');
    $user = User::factory()->create();
    $task = taskAt($user, 'Morgen', now()->addDay()->setTime(10, 0));

    $upcoming = dashboardFor($user)['upcoming'];

    expect($upcoming[0]['title'])->toBe('Morgen');
    expect($upcoming[0]['occurrences']->pluck('task.id'))->toContain($task->id);
});

test('a task in three days gets its own day group', function () {
    $user = User::factory()->create();
    $task = taskAt($user, 'In drei Tagen', now()->addDays(3)->setTime(10, 0));

    $found = collect(dashboardFor($user)['upcoming'])
        ->flatMap(fn ($group) => $group['occurrences']->pluck('task.id'));

    expect($found)->toContain($task->id);
});

test('a task beyond the week lands in later', function () {
    $user = User::factory()->create();
    $task = taskAt($user, 'Weit weg', now()->addDays(20)->setTime(10, 0));

    expect(dashboardFor($user)['later']->pluck('task.id'))->toContain($task->id);
});

test('a task without a date lands in undated', function () {
    $user = User::factory()->create();
    $task = taskAt($user, 'Irgendwann', null);

    expect(dashboardFor($user)['undated']->pluck('task.id'))->toContain($task->id);
});

test('a completed occurrence appears in no group', function () {
    $user = User::factory()->create();
    $task = taskAt($user, 'Erledigt', now()->addDay()->setTime(10, 0));
    $task->complete(null, $user);

    $groups = dashboardFor($user);
    $all = collect([$groups['overdue'], $groups['today'], $groups['later'], $groups['undated']])
        ->flatMap(fn ($c) => $c->pluck('task.id'))
        ->merge(collect($groups['upcoming'])->flatMap(fn ($g) => $g['occurrences']->pluck('task.id')));

    expect($all)->not->toContain($task->id);
});

test('the open count for today ignores everything but today', function () {
    $user = User::factory()->create();
    taskAt($user, 'Heute', now()->endOfDay()->subHour());
    taskAt($user, 'Morgen', now()->addDay()->setTime(10, 0));
    taskAt($user, 'Gestern', now()->subDay()->setTime(10, 0));

    Livewire::actingAs($user)->test(TaskManager::class)->assertViewHas('todayCount', 1);
});

test('the day headline and the progress of today are rendered', function () {
    app()->setLocale('de');

    $user = User::factory()->create();
    taskAt($user, 'Heute offen', now()->endOfDay()->subHour());
    taskAt($user, 'Heute erledigt', now()->startOfDay()->addHours(2))->complete(null, $user);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->assertSee(now()->translatedFormat('l, j. F'))
        ->assertViewHas('todayDoneCount', 1)
        ->assertSee('1 offen')
        ->assertSee('1 erledigt');
});

test('completions of other people do not count towards my day', function () {
    $user = User::factory()->create();
    $stranger = User::factory()->create();
    taskAt($stranger, 'Fremd', now()->startOfDay()->addHours(2))->complete(null, $stranger);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->assertViewHas('todayDoneCount', 0);
});
