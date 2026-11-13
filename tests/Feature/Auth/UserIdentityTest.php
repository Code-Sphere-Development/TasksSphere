<?php

use App\Models\User;
use CodeSphere\OAuth\Services\CodeSphereService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('other users show their own name and email, not those of the session', function () {
    // Der Trait aus code-sphere/oauth liest name/email aus der Session. Ohne
    // die Ueberschreibung im Modell truegen alle Mitglieder diese Werte.
    app(CodeSphereService::class)->cacheProfile([
        'user' => ['id' => 1, 'name' => 'Session Person', 'email' => 'session@example.com'],
        'groups' => [],
    ]);

    $other = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
    ]);

    expect($other->name)->toBe('Jane Doe')
        ->and($other->email)->toBe('jane@example.com')
        ->and($other->toArray())->toMatchArray(['name' => 'Jane Doe', 'email' => 'jane@example.com']);
});

test('the profile photo url is an initials avatar', function () {
    $user = User::factory()->make(['first_name' => 'Jane', 'last_name' => 'Doe']);

    expect($user->profile_photo_url)->toBe('https://ui-avatars.com/api/?name=J+D&color=7F9CF5&background=EBF4FF');
});

test('a profile without a name keeps the previous name', function () {
    $user = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

    $user->syncFromCodeSphere(['id' => 1, 'email' => 'new@example.com']);

    expect($user->fresh()->only('first_name', 'last_name', 'email'))
        ->toBe(['first_name' => 'Jane', 'last_name' => 'Doe', 'email' => 'new@example.com']);
});

test('a single word name clears the old last name', function () {
    $user = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Doe']);

    $user->syncFromCodeSphere(['name' => 'Cher']);

    expect($user->fresh()->only('first_name', 'last_name'))
        ->toBe(['first_name' => 'Cher', 'last_name' => null]);
});
