<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('an existing account can be linked by id', function () {
    $user = User::factory()->unlinked()->create();

    $this->artisan('users:link-codesphere', ['user' => $user->id, 'codesphere_id' => 7])
        ->assertSuccessful();

    expect($user->fresh()->codesphere_id)->toBe(7);
});

test('an existing account can be linked by email', function () {
    $user = User::factory()->unlinked()->create(['email' => 'jane@example.com']);

    $this->artisan('users:link-codesphere', ['user' => 'jane@example.com', 'codesphere_id' => 7])
        ->assertSuccessful();

    expect($user->fresh()->codesphere_id)->toBe(7);
});

test('an empty stub from an earlier SSO login is replaced', function () {
    $user = User::factory()->unlinked()->create(['email' => 'jane@example.com']);
    $stub = User::factory()->create(['codesphere_id' => 7, 'email' => 'jane@example.com']);
    $stub->createToken('mobile-app');

    $this->artisan('users:link-codesphere', ['user' => 'jane@example.com', 'codesphere_id' => 7])
        ->assertSuccessful();

    expect($user->fresh()->codesphere_id)->toBe(7);
    $this->assertModelMissing($stub);
    $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $stub->id]);
});

test('a stub that already owns data is never deleted', function () {
    $user = User::factory()->unlinked()->create();
    $stub = User::factory()->create(['codesphere_id' => 7]);
    Task::factory()->create(['user_id' => $stub->id]);

    $this->artisan('users:link-codesphere', ['user' => $user->id, 'codesphere_id' => 7])
        ->assertFailed();

    $this->assertModelExists($stub);
    expect($user->fresh()->codesphere_id)->toBeNull();
});

test('an account linked to someone else is not relinked', function () {
    $user = User::factory()->create(['codesphere_id' => 3]);

    $this->artisan('users:link-codesphere', ['user' => $user->id, 'codesphere_id' => 7])
        ->assertFailed();

    expect($user->fresh()->codesphere_id)->toBe(3);
});

test('an unknown account is reported', function () {
    $this->artisan('users:link-codesphere', ['user' => 999, 'codesphere_id' => 7])
        ->assertFailed();
});
