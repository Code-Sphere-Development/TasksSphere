<?php

use App\Enums\TaskPriority;
use App\Enums\TaskRotation;
use App\Livewire\TaskManager;
use App\Models\Household;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\TaskList;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('it only shows completed tasks for the logged in user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Create tasks for both users
    $task1 = Task::factory()->create([
        'user_id' => $user1->id,
        'title' => 'User 1 Task',
        'recurrence_rule' => null,
        'is_archived' => false,
    ]);
    $task2 = Task::factory()->create([
        'user_id' => $user2->id,
        'title' => 'User 2 Task',
        'recurrence_rule' => null,
        'is_archived' => false,
    ]);

    // Mark tasks as completed
    TaskCompletion::create([
        'task_id' => $task1->id,
        'completed_at' => now(),
        'planned_at' => now(),
        'is_skipped' => false,
    ]);

    TaskCompletion::create([
        'task_id' => $task2->id,
        'completed_at' => now(),
        'planned_at' => now(),
        'is_skipped' => false,
    ]);

    // Test as user 1
    Livewire::actingAs($user1)
        ->test(TaskManager::class)
        ->assertViewHas('completedCompletions', function ($completions) use ($task1, $task2) {
            return $completions->contains('task_id', $task1->id) &&
                   ! $completions->contains('task_id', $task2->id);
        });

    // Test as user 2
    Livewire::actingAs($user2)
        ->test(TaskManager::class)
        ->assertViewHas('completedCompletions', function ($completions) use ($task1, $task2) {
            return $completions->contains('task_id', $task2->id) &&
                   ! $completions->contains('task_id', $task1->id);
        });
});

test('opening the task detail exposes the task to the view', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Rechnungen sortieren',
        'description' => 'Alle offenen Rechnungen des Monats ablegen.',
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->call('showTaskDetail', $task->id, '2026-09-14 08:00:00')
        ->assertSet('detailTaskId', $task->id)
        ->assertSet('detailPlannedAt', '2026-09-14 08:00:00')
        ->assertViewHas('detailTask', fn ($detailTask) => $detailTask?->is($task));
});

test('the task detail cannot be opened for someone elses task', function () {
    $user = User::factory()->create();
    $foreignTask = Task::factory()->create(['is_archived' => false]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->call('showTaskDetail', $foreignTask->id)
        ->assertForbidden();
});

test('closing the task detail clears its state', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'is_archived' => false]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->call('showTaskDetail', $task->id)
        ->call('closeTaskDetail')
        ->assertSet('detailTaskId', null)
        ->assertViewHas('detailTask', null);
});

test('editing from the task detail fills the form and closes the detail', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Backup prüfen',
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->call('showTaskDetail', $task->id)
        ->call('editTask', $task->id)
        ->assertSet('title', 'Backup prüfen')
        ->assertSet('isEditing', true)
        ->assertSet('detailTaskId', null);
});

test('the dashboard renders no detail modal before one is opened', function () {
    $user = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'is_archived' => false]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->assertDontSeeHtml('wire:click="closeTaskDetail"');
});

test('the detail modal shows list, source and recurrence of the task', function () {
    app()->setLocale('de');

    $user = User::factory()->create();
    $list = TaskList::factory()->create(['user_id' => $user->id, 'title' => 'Haushalt']);
    $task = Task::factory()->agent()->create([
        'user_id' => $user->id,
        'task_list_id' => $list->id,
        'title' => 'Müll rausbringen',
        'is_archived' => false,
        'recurrence_rule' => ['frequency' => 'weekly', 'interval' => 1, 'weekdays' => [1, 4], 'times' => ['07:30']],
        'recurrence_timezone' => 'Europe/Berlin',
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->call('showTaskDetail', $task->id)
        ->assertSeeHtml('wire:click="closeTaskDetail"')
        ->assertSee('Haushalt')
        ->assertSee('Agent')
        ->assertSee('Wöchentlich, Mo und Do, um 07:30 Uhr (Europe/Berlin)');
});

test('each task card offers a control that opens its detail', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Steuer vorbereiten',
        'due_at' => now()->addDay()->setTime(9, 0),
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->assertSeeHtml('wire:click="showTaskDetail('.$task->id);
});

test('the detail modal labels are translated for the english locale', function () {
    app()->setLocale('en');

    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'due_at' => now()->addDay()->setTime(9, 0),
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->call('showTaskDetail', $task->id)
        ->assertSee('Due on')
        ->assertSee('Created on')
        ->assertDontSee('Fällig am');
});

test('the detail modal wires the complete action to the shown occurrence', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create(['user_id' => $user->id, 'is_archived' => false]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->call('showTaskDetail', $task->id, '2026-09-14 08:00:00')
        ->assertSeeHtml('wire:click="completeTask('.$task->id.", '2026-09-14 08:00:00')\"");
});

test('completing a task from the detail closes the detail', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'due_at' => now()->addDay(),
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->call('showTaskDetail', $task->id)
        ->call('completeTask', $task->id)
        ->assertSet('detailTaskId', null);

    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('a completed one off task appears under recently completed', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Einmalige Aufgabe',
        'due_at' => now()->addHour(),
        'recurrence_rule' => null,
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->call('completeTask', $task->id);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->assertViewHas('completedCompletions', fn ($completions) => $completions->contains('task_id', $task->id))
        ->assertSee('Einmalige Aufgabe');
});

test('a task can be created with a priority', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->set('title', 'Steuer')
        ->set('priority', TaskPriority::Urgent->value)
        ->call('createTask');

    expect($user->tasks()->first()->priority)->toBe(TaskPriority::Urgent);
});

test('editing a task prefills its priority', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'priority' => TaskPriority::High,
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->call('editTask', $task->id)
        ->assertSet('priority', TaskPriority::High->value);
});

test('a task can be created without a priority', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->set('title', 'Ohne Prioritaet')
        ->call('createTask');

    expect($user->tasks()->first()->priority)->toBeNull();
});

test('the priority is shown on the task card', function () {
    app()->setLocale('de');

    $user = User::factory()->create();
    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Steuer',
        'priority' => TaskPriority::Urgent,
        'due_at' => now()->addHour(),
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->assertSee('Dringend');
});

test('completing from the dashboard records the acting user as completer', function () {
    $owner = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'due_at' => now()->addHour(),
        'is_archived' => false,
    ]);

    Livewire::actingAs($owner)
        ->test(TaskManager::class)
        ->call('completeTask', $task->id);

    expect($task->completions()->first()->completed_by)->toBe($owner->id);
});

test('a task assigned to me appears on my dashboard', function () {
    $owner = User::factory()->create();
    $me = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $owner->id,
        'title' => 'Zugewiesene Aufgabe',
        'due_at' => now()->addHour(),
        'is_archived' => false,
    ]);
    $task->assignTo($me, $owner);

    Livewire::actingAs($me)
        ->test(TaskManager::class)
        ->assertSee('Zugewiesene Aufgabe');
});

test('a task of someone else does not appear on my dashboard', function () {
    $me = User::factory()->create();
    Task::factory()->create([
        'title' => 'Fremde Aufgabe',
        'due_at' => now()->addHour(),
        'is_archived' => false,
    ]);

    Livewire::actingAs($me)
        ->test(TaskManager::class)
        ->assertDontSee('Fremde Aufgabe');
});

test('a task can be created with a responsible person', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    Household::createFor($owner, 'Familie')->addMember($member);

    Livewire::actingAs($owner)
        ->test(TaskManager::class)
        ->set('title', 'Muell')
        ->set('assigned_to', $member->id)
        ->call('createTask');

    $task = $owner->tasks()->first();
    expect($task->assigned_to)->toBe($member->id);
    expect($task->assignees->pluck('id'))->toContain($member->id);
});

test('a person outside my households cannot be made responsible', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    Livewire::actingAs($owner)
        ->test(TaskManager::class)
        ->set('title', 'Muell')
        ->set('assigned_to', $stranger->id)
        ->call('createTask')
        ->assertHasErrors('assigned_to');

    expect($owner->tasks()->count())->toBe(0);
});

test('editing a task prefills the responsible person', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    Household::createFor($owner, 'Familie')->addMember($member);
    $task = Task::factory()->for($owner)->create(['is_archived' => false]);
    $task->assignTo($member, $owner);

    Livewire::actingAs($owner)
        ->test(TaskManager::class)
        ->call('editTask', $task->id)
        ->assertSet('assigned_to', $member->id);
});

test('the assignable people are offered to the view', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $stranger = User::factory()->create();
    Household::createFor($owner, 'Familie')->addMember($member);

    Livewire::actingAs($owner)
        ->test(TaskManager::class)
        ->assertViewHas('assignablePeople', fn ($people) => $people->contains('id', $member->id)
            && ! $people->contains('id', $stranger->id));
});

test('a task can be created with a circle and a rotation strategy', function () {
    $owner = User::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();
    $household = Household::createFor($owner, 'Familie');
    $household->addMember($a);
    $household->addMember($b);

    Livewire::actingAs($owner)
        ->test(TaskManager::class)
        ->set('title', 'Muell')
        ->set('assignees', [$a->id, $b->id])
        ->set('rotation_strategy', TaskRotation::LeastCompleted->value)
        ->call('createTask');

    $task = $owner->tasks()->first();
    expect($task->assignees->pluck('id')->sort()->values()->all())->toBe(collect([$a->id, $b->id])->sort()->values()->all());
    expect($task->rotation_strategy)->toBe(TaskRotation::LeastCompleted);
});

test('the circle cannot contain someone outside my households', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    Livewire::actingAs($owner)
        ->test(TaskManager::class)
        ->set('title', 'Muell')
        ->set('assignees', [$stranger->id])
        ->call('createTask')
        ->assertHasErrors('assignees.0');
});

test('editing a task prefills the circle and the rotation strategy', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    Household::createFor($owner, 'Familie')->addMember($member);
    $task = Task::factory()->for($owner)->create([
        'is_archived' => false,
        'rotation_strategy' => TaskRotation::Random,
    ]);
    $task->assignTo($member, $owner);

    Livewire::actingAs($owner)
        ->test(TaskManager::class)
        ->call('editTask', $task->id)
        ->assertSet('assignees', [$member->id])
        ->assertSet('rotation_strategy', TaskRotation::Random->value);
});

test('updating a task can shrink the circle', function () {
    $owner = User::factory()->create();
    $a = User::factory()->create();
    $b = User::factory()->create();
    $household = Household::createFor($owner, 'Familie');
    $household->addMember($a);
    $household->addMember($b);
    $task = Task::factory()->for($owner)->create(['is_archived' => false]);
    $task->assignees()->attach([$a->id, $b->id]);

    Livewire::actingAs($owner)
        ->test(TaskManager::class)
        ->call('editTask', $task->id)
        ->set('assignees', [$a->id])
        ->call('updateTask');

    expect($task->fresh()->assignees->pluck('id')->all())->toBe([$a->id]);
});

test('the task row shows a preview of the description', function () {
    $user = User::factory()->create();
    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Reifen wechseln',
        'description' => 'Termin bei der Werkstatt vereinbaren.',
        'due_at' => now()->addHour(),
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->assertSee('Termin bei der Werkstatt vereinbaren.');
});

test('a leading reference is shown apart from the title', function () {
    $user = User::factory()->create();
    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'FamilyNetwork#14 . Einkaufsliste ergänzen',
        'due_at' => now()->addHour(),
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->assertSeeInOrder(['FamilyNetwork#14', 'Einkaufsliste ergänzen']);
});

test('upcoming tasks appear in the main column with a complete control', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Zahnarzttermin',
        'due_at' => now()->addDay()->setTime(10, 15),
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->assertSee('Zahnarzttermin')
        ->assertSeeHtml('wire:click="completeTask('.$task->id);
});

test('a later task can be completed in one step', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Versicherung kündigen',
        'due_at' => now()->addDays(20)->setTime(12, 0),
        'is_archived' => false,
    ]);

    Livewire::actingAs($user)
        ->test(TaskManager::class)
        ->assertSeeHtml('wire:click="completeTask('.$task->id)
        ->call('completeTask', $task->id)
        ->assertOk();

    expect($task->fresh()->completed_at)->not->toBeNull();
});

test('completed tasks no longer occupy the main column', function () {
    app()->setLocale('de');

    $user = User::factory()->create();
    Task::factory()->create([
        'user_id' => $user->id,
        'title' => 'Paket abholen',
        'due_at' => now()->subHour(),
        'is_archived' => false,
    ])->complete(null, $user);

    $html = Livewire::actingAs($user)->test(TaskManager::class)->html();

    // Erledigtes steht in der Randspalte, Offenes in der Hauptspalte.
    $before = substr($html, 0, strpos($html, 'Zuletzt erledigt'));
    expect($before)->toContain('<aside');
    expect($html)->toContain('Paket abholen');
});

test('later and undated tasks sit in the main column, not the sidebar', function () {
    app()->setLocale('de');

    $user = User::factory()->create();
    Task::factory()->create(['user_id' => $user->id, 'title' => 'Versicherung kündigen', 'due_at' => now()->addDays(20), 'is_archived' => false]);
    Task::factory()->create(['user_id' => $user->id, 'title' => 'Fahrradschlauch flicken', 'due_at' => null, 'is_archived' => false]);

    $html = Livewire::actingAs($user)->test(TaskManager::class)->html();
    $asideStart = strpos($html, '<aside');

    expect(strpos($html, 'Versicherung kündigen'))->toBeLessThan($asideStart);
    expect(strpos($html, 'Fahrradschlauch flicken'))->toBeLessThan($asideStart);
});
