<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
    $response->assertJsonMissing(['password']);
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

test('update changes the user profile fields', function () {
    $user = User::factory()->create([
        'first_name' => 'Old',
        'last_name' => 'Name',
        'email' => 'old@example.com',
        'language' => 'en',
    ]);
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile', [
        'first_name' => 'New',
        'last_name' => 'Person',
        'email' => 'new@example.com',
        'language' => 'de',
    ]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => 'Profil aktualisiert',
            'user' => [
                'first_name' => 'New',
                'last_name' => 'Person',
                'email' => 'new@example.com',
                'language' => 'de',
            ],
        ]);

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'first_name' => 'New',
        'last_name' => 'Person',
        'email' => 'new@example.com',
        'language' => 'de',
    ]);
});

test('update hashes a new password when provided', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertStatus(200);

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

test('update keeps the existing password when none is provided', function () {
    $user = User::factory()->create([
        'password' => bcrypt('original-password'),
    ]);
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ]);

    $response->assertStatus(200);

    expect(Hash::check('original-password', $user->fresh()->password))->toBeTrue();
});

test('update allows the user to keep their own email', function () {
    $user = User::factory()->create(['email' => 'me@example.com']);
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'me@example.com',
    ]);

    $response->assertStatus(200);
});

test('update rejects an email already used by another user', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    $user = User::factory()->create(['email' => 'me@example.com']);
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'taken@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('update requires the mandatory fields', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['first_name', 'last_name', 'email']);
});

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

test('update rejects an unconfirmed password', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->putJson('/api/profile', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'mismatch',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

test('update requires authentication', function () {
    $response = $this->putJson('/api/profile', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ]);

    $response->assertStatus(401);
});
