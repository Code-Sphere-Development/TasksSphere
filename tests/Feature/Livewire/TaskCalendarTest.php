<?php

use App\Livewire\TaskCalendar;
use App\Models\Household;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the page requires authentication', function () {
    $this->get('/calendar')->assertRedirect('/login');
});

test('it shows a task due in the displayed month', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create([
        'title' => 'Zahnarzt',
        'due_at' => now()->startOfMonth()->addDays(10)->setTime(9, 0),
        'is_archived' => false,
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(TaskCalendar::class)
        ->assertSee('Zahnarzt');
});

test('it does not show a task of another month', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create([
        'title' => 'Weit weg',
        'due_at' => now()->addMonths(3)->startOfMonth()->addDay(),
        'is_archived' => false,
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(TaskCalendar::class)
        ->assertDontSee('Weit weg');
});

test('paging forward reaches the next month', function () {
    $user = User::factory()->create();
    Task::factory()->for($user)->create([
        'title' => 'Naechster Monat',
        'due_at' => now()->addMonth()->startOfMonth()->addDays(3)->setTime(9, 0),
        'is_archived' => false,
        'completed_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test(TaskCalendar::class)
        ->assertDontSee('Naechster Monat')
        ->call('nextMonth')
        ->assertSee('Naechster Monat');
});

test('it shows a task assigned to me but owned by someone else', function () {
    $owner = User::factory()->create();
    $me = User::factory()->create();
    $task = Task::factory()->for($owner)->create([
        'title' => 'Zugewiesen',
        'due_at' => now()->startOfMonth()->addDays(5)->setTime(9, 0),
        'is_archived' => false,
        'completed_at' => null,
    ]);
    $task->assignTo($me, $owner);

    Livewire::actingAs($me)
        ->test(TaskCalendar::class)
        ->assertSee('Zugewiesen');
});

test('filtering by person narrows the calendar', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    Household::createFor($owner, 'Familie')->addMember($member);

    $mine = Task::factory()->for($owner)->create([
        'title' => 'Meine Aufgabe',
        'due_at' => now()->startOfMonth()->addDays(4)->setTime(9, 0),
        'is_archived' => false,
        'completed_at' => null,
    ]);
    $theirs = Task::factory()->for($owner)->create([
        'title' => 'Aufgabe des Mitglieds',
        'due_at' => now()->startOfMonth()->addDays(5)->setTime(9, 0),
        'is_archived' => false,
        'completed_at' => null,
    ]);
    $theirs->assignTo($member, $owner);

    Livewire::actingAs($owner)
        ->test(TaskCalendar::class)
        ->set('personId', $member->id)
        ->assertSee('Aufgabe des Mitglieds')
        ->assertDontSee('Meine Aufgabe');
});

test('the month is reflected in the url', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(TaskCalendar::class)
        ->call('nextMonth')
        ->assertSet('month', now()->addMonth()->format('Y-m'));
});
