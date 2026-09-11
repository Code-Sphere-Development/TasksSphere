<?php

use App\Enums\TaskSource;
use App\Livewire\TaskManager;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Herkunftskennzeichnung
|--------------------------------------------------------------------------
|
| Der Client darf source mitschicken. Fehlt es, leitet der Server die Herkunft
| aus dem Namen des Sanctum-Tokens ab (config/tasks.php). Alles Unbekannte
| gilt als manuell angelegt.
|
*/

test('a task defaults to manual when no source is given', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*'], 'sanctum');

    $this->postJson('/api/tasks', ['title' => 'Ohne Herkunft'])
        ->assertCreated()
        ->assertJsonPath('source', 'manual');

    expect(Task::first()->source)->toBe(TaskSource::Manual);
});

test('the client may state the source explicitly', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*'], 'sanctum');

    $this->postJson('/api/tasks', ['title' => 'Vom Agenten', 'source' => 'agent'])
        ->assertCreated()
        ->assertJsonPath('source', 'agent');

    expect(Task::first()->source)->toBe(TaskSource::Agent);
});

test('an unknown source is rejected', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*'], 'sanctum');

    $this->postJson('/api/tasks', ['title' => 'Krumm', 'source' => 'alexa'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('source');
});

test('the source is derived from the token name when the client stays silent', function () {
    config()->set('tasks.sources_by_token_name', ['gehirn-agent' => 'agent']);

    $user = User::factory()->create();
    $token = $user->createToken('gehirn-agent');

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/tasks', ['title' => 'Aus dem Homelab'])
        ->assertCreated()
        ->assertJsonPath('source', 'agent');
});

test('an explicit source wins over the token name', function () {
    config()->set('tasks.sources_by_token_name', ['gehirn-agent' => 'agent']);

    $user = User::factory()->create();
    $token = $user->createToken('gehirn-agent');

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/tasks', ['title' => 'Eingelesen', 'source' => 'import'])
        ->assertCreated()
        ->assertJsonPath('source', 'import');
});

test('an unmapped token name means manual', function () {
    config()->set('tasks.sources_by_token_name', ['gehirn-agent' => 'agent']);

    $user = User::factory()->create();
    $token = $user->createToken('flutter_app');

    $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/tasks', ['title' => 'Vom Handy'])
        ->assertCreated()
        ->assertJsonPath('source', 'manual');
});

test('the source cannot be changed afterwards', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*'], 'sanctum');

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'source' => TaskSource::Agent,
    ]);

    $this->putJson("/api/tasks/{$task->id}", [
        'title' => 'Umbenannt',
        'source' => 'manual',
    ])->assertOk();

    expect($task->fresh()->source)->toBe(TaskSource::Agent);
});

test('a task created through the web interface is manual', function () {
    $user = User::factory()->create(['timezone' => 'Europe/Berlin']);

    $this->actingAs($user);

    Livewire\Livewire::test(TaskManager::class)
        ->set('title', 'Im Browser angelegt')
        ->set('recurrence_timezone', 'Europe/Berlin')
        ->call('createTask');

    expect(Task::first()->source)->toBe(TaskSource::Manual);
});
