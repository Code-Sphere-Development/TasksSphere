<?php

use App\Livewire\ApiTokenManager;
use App\Livewire\DeleteAccountForm;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the profile page shows the snapshot and links to AccountSphere', function () {
    $user = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com']);

    $this->actingAs($user)
        ->get('/user/profile')
        ->assertOk()
        ->assertSee('Jane Doe')
        ->assertSee('jane@example.com')
        ->assertSee('https://account.code-sphere.de/profile');
});

test('the api token page renders', function () {
    $this->actingAs(User::factory()->create())
        ->get('/user/api-tokens')
        ->assertOk()
        ->assertSeeLivewire(ApiTokenManager::class);
});

test('api tokens can be created with full rights', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(ApiTokenManager::class)
        ->set('name', 'gehirn-agent')
        ->call('createApiToken')
        ->assertSet('displayingToken', true)
        ->assertNotSet('plainTextToken', null);

    $token = $user->tokens()->sole();
    expect($token->name)->toBe('gehirn-agent')
        ->and($token->abilities)->toBe(['*']);
});

test('api tokens need a name', function () {
    Livewire::actingAs(User::factory()->create())
        ->test(ApiTokenManager::class)
        ->call('createApiToken')
        ->assertHasErrors(['name' => 'required']);
});

test('api tokens can be deleted', function () {
    $user = User::factory()->create();
    $token = $user->createToken('alt')->accessToken;

    Livewire::actingAs($user)
        ->test(ApiTokenManager::class)
        ->call('confirmApiTokenDeletion', $token->id)
        ->call('deleteApiToken');

    expect($user->tokens()->count())->toBe(0);
});

test('nobody can delete tokens of another user', function () {
    $owner = User::factory()->create();
    $token = $owner->createToken('fremd')->accessToken;

    Livewire::actingAs(User::factory()->create())
        ->test(ApiTokenManager::class)
        ->call('confirmApiTokenDeletion', $token->id)
        ->call('deleteApiToken');

    expect($owner->tokens()->count())->toBe(1);
});

test('the account can be deleted after confirming the email', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);
    $user->createToken('mobile-app');

    Livewire::actingAs($user)
        ->test(DeleteAccountForm::class)
        ->set('confirmation', ' Jane@Example.com ')
        ->call('deleteUser')
        ->assertRedirect('/');

    $this->assertModelMissing($user);
    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $user->id]);
});

test('the account is kept when the confirmation does not match', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    Livewire::actingAs($user)
        ->test(DeleteAccountForm::class)
        ->set('confirmation', 'someone@example.com')
        ->call('deleteUser')
        ->assertHasErrors('confirmation');

    $this->assertModelExists($user);
});
