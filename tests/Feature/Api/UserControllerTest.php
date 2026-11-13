<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| show
|--------------------------------------------------------------------------
*/

test('show returns the authenticated user profile', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ]);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/profile');

    $response->assertStatus(200)
        ->assertJson([
            'id' => $user->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
        ]);

    // Hidden attributes must not leak.
    $response->assertJsonMissingPath('access_token')
        ->assertJsonMissingPath('refresh_token');
});

test('show requires authentication', function () {
    $response = $this->getJson('/api/profile');

    $response->assertStatus(401);
});

/*
|--------------------------------------------------------------------------
| update
|--------------------------------------------------------------------------
*/

test('update changes the language and accepts unchanged identity fields', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'language' => 'en',
    ]);
    Sanctum::actingAs($user);

    // So schickt die Mobile-App ihr Profilformular.
    $response = $this->putJson('/api/profile', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'language' => 'de',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Profil aktualisiert',
            'user' => ['language' => 'de'],
        ]);

    expect($user->fresh()->language)->toBe('de');
});

test('update accepts the language alone', function () {
    $user = User::factory()->create(['language' => 'en']);
    Sanctum::actingAs($user);

    $this->putJson('/api/profile', ['language' => 'de'])->assertStatus(200);

    expect($user->fresh()->language)->toBe('de');
});

test('update refuses to change fields owned by AccountSphere', function (array $change) {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ]);
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile', array_merge([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ], $change));

    $response->assertStatus(422)
        ->assertJsonValidationErrors(array_keys(array_diff_key($change, ['password_confirmation' => true])));

    expect($user->fresh()->only('first_name', 'last_name', 'email'))
        ->toBe(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'jane@example.com']);
})->with([
    'first name' => [['first_name' => 'John']],
    'last name' => [['last_name' => 'Smith']],
    'email' => [['email' => 'new@example.com']],
    'password' => [['password' => 'new-password', 'password_confirmation' => 'new-password']],
]);

test('update rejects an unsupported language', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'language' => 'fr',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['language']);
});

test('update requires authentication', function () {
    $response = $this->putJson('/api/profile', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ]);

    $response->assertStatus(401);
});
