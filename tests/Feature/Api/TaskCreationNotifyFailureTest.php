<?php

use App\Models\Task;
use App\Models\User;
use App\Notifications\TaskReminderNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\ServerUnavailable;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Anlegen mit notify, wenn der Push scheitert
|--------------------------------------------------------------------------
| Gemeldet vom Agenten: POST /api/tasks mit notify=true legt die Aufgabe an
| und antwortet trotzdem 500. Der Versand darf das Anlegen nicht kippen.
*/

function userWithDevice(): User
{
    $user = User::factory()->create();
    $user->devices()->create([
        'device_id' => 'geraet-1',
        'fcm_token' => 'token-1',
        'last_active_at' => now(),
    ]);

    return $user;
}

test('a failing push does not turn the creation into a server error', function () {
    $user = userWithDevice();
    Sanctum::actingAs($user);

    // Stellvertretend fuer jede Stoerung im Push-Pfad: fehlende Zugangsdaten,
    // abgelaufenes Dienstkonto, FCM nicht erreichbar.
    $this->mock(Messaging::class, function ($mock) {
        $mock->shouldReceive('sendMulticast')
            ->andThrow(new ServerUnavailable('Firebase nicht erreichbar'));
    });

    $this->postJson('/api/tasks', ['title' => 'Mit Erinnerung', 'notify' => true])
        ->assertCreated()
        ->assertJsonPath('title', 'Mit Erinnerung');

    $this->assertDatabaseHas('tasks', ['title' => 'Mit Erinnerung', 'user_id' => $user->id]);
});

test('a broken firebase configuration does not turn the creation into a server error', function () {
    // Zweiter Weg in denselben Fehler: schon das Aufloesen des Kanals wirft,
    // wenn die Zugangsdaten fehlen - unabhaengig von angemeldeten Geraeten.
    config(['firebase.projects.taskssphere.credentials' => storage_path('app/gibt-es-nicht.json')]);

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson('/api/tasks', ['title' => 'Ohne Firebase', 'notify' => true])
        ->assertCreated();

    $this->assertDatabaseHas('tasks', ['title' => 'Ohne Firebase', 'user_id' => $user->id]);
});

test('the failed delivery is logged with the task', function () {
    $user = userWithDevice();
    Sanctum::actingAs($user);

    $this->mock(Messaging::class, function ($mock) {
        $mock->shouldReceive('sendMulticast')->andThrow(new ServerUnavailable('Firebase nicht erreichbar'));
    });

    Log::shouldReceive('error')
        ->once()
        ->withArgs(fn (string $message, array $context) => str_contains($message, 'Firebase nicht erreichbar')
            && $context['user_id'] === $user->id);

    $this->postJson('/api/tasks', ['title' => 'Mit Erinnerung', 'notify' => true])->assertCreated();
});

test('the reminder is sent through the queue', function () {
    expect(new TaskReminderNotification(Task::factory()->create()))
        ->toBeInstanceOf(ShouldQueue::class);
});
