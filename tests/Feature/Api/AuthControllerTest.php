<?php

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| register
|--------------------------------------------------------------------------
*/

test('register creates a user and returns a token', function () {
    $response = $this->postJson('/api/register', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'device_name' => 'mobile-app',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'first_name', 'last_name', 'email'],
        ]);

    expect($response->json('token'))->not->toBeEmpty();

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'first_name' => 'Jane',
        'last_name' => 'Doe',
    ]);

    // Sanctum token persisted for the new user.
    $user = User::where('email', 'jane@example.com')->first();
    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'name' => 'mobile-app',
    ]);
});

test('register requires all mandatory fields', function () {
    $response = $this->postJson('/api/register', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'first_name',
            'last_name',
            'email',
            'password',
            'device_name',
        ]);
});

test('register rejects a duplicate email', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson('/api/register', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'device_name' => 'mobile-app',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('register requires the password to be confirmed', function () {
    $response = $this->postJson('/api/register', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different456',
        'device_name' => 'mobile-app',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['password']);
});

/*
|--------------------------------------------------------------------------
| login
|--------------------------------------------------------------------------
*/

test('login rejects invalid credentials', function () {
    $user = User::factory()->create([
        'password' => bcrypt('secret123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
        'device_name' => 'mobile-app',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('login rejects an unknown email', function () {
    $response = $this->postJson('/api/login', [
        'email' => 'nobody@example.com',
        'password' => 'secret123',
        'device_name' => 'mobile-app',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('login requires email, password and device name', function () {
    $response = $this->postJson('/api/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password', 'device_name']);
});

test('login updates the timezone from the header', function () {
    $user = User::factory()->create([
        'password' => bcrypt('secret123'),
        'timezone' => 'UTC',
    ]);

    $response = $this->postJson('/api/login', [
        'email' => $user->email,
        'password' => 'secret123',
        'device_name' => 'mobile-app',
    ], [
        'X-Timezone' => 'Europe/Berlin',
    ]);

    $response->assertStatus(200);

    expect($user->fresh()->timezone)->toBe('Europe/Berlin');
});

/*
|--------------------------------------------------------------------------
| forgotPassword
|--------------------------------------------------------------------------
*/

test('forgot password sends a reset link for an existing user', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->postJson('/api/forgot-password', [
        'email' => $user->email,
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['message']);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('forgot password fails for an unknown email', function () {
    Notification::fake();

    $response = $this->postJson('/api/forgot-password', [
        'email' => 'nobody@example.com',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    Notification::assertNothingSent();
});

test('forgot password requires a valid email', function () {
    $response = $this->postJson('/api/forgot-password', [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

/*
|--------------------------------------------------------------------------
| logout
|--------------------------------------------------------------------------
*/

test('logout deletes the current access token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('mobile-app');

    $response = $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken)
        ->postJson('/api/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Abgemeldet']);

    $this->assertDatabaseMissing('personal_access_tokens', [
        'id' => $token->accessToken->id,
    ]);
});

test('logout requires authentication', function () {
    $response = $this->postJson('/api/logout');

    $response->assertStatus(401);
});

/*
|--------------------------------------------------------------------------
| updateFcmToken
|--------------------------------------------------------------------------
*/

test('update fcm token stores the device for the user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/fcm-token', [
        'fcm_token' => 'token-abc',
        'device_id' => 'device-1',
    ]);

    $response->assertStatus(200)
        ->assertJson(['message' => 'FCM Token aktualisiert']);

    $this->assertDatabaseHas('user_devices', [
        'user_id' => $user->id,
        'fcm_token' => 'token-abc',
        'device_id' => 'device-1',
    ]);

    expect($user->fresh()->fcm_token)->toBe('token-abc');
});

test('update fcm token updates the timezone when provided', function () {
    $user = User::factory()->create(['timezone' => 'UTC']);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/fcm-token', [
        'fcm_token' => 'token-abc',
        'timezone' => 'Europe/Berlin',
    ]);

    $response->assertStatus(200);

    expect($user->fresh()->timezone)->toBe('Europe/Berlin');
});

test('update fcm token requires the fcm token field', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/fcm-token', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['fcm_token']);
});

test('update fcm token rejects an invalid timezone', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/fcm-token', [
        'fcm_token' => 'token-abc',
        'timezone' => 'Not/AZone',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['timezone']);
});

test('update fcm token requires authentication', function () {
    $response = $this->postJson('/api/fcm-token', [
        'fcm_token' => 'token-abc',
    ]);

    $response->assertStatus(401);
});

test('update fcm token reassigns a token from another user', function () {
    $otherUser = User::factory()->create();
    UserDevice::factory()->create([
        'user_id' => $otherUser->id,
        'fcm_token' => 'shared-token',
        'device_id' => 'old-device',
    ]);

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/fcm-token', [
        'fcm_token' => 'shared-token',
        'device_id' => 'new-device',
    ]);

    $response->assertStatus(200);

    // The token was removed from the previous owner and attached to the new user.
    $this->assertDatabaseMissing('user_devices', [
        'user_id' => $otherUser->id,
        'fcm_token' => 'shared-token',
    ]);

    $this->assertDatabaseHas('user_devices', [
        'user_id' => $user->id,
        'fcm_token' => 'shared-token',
        'device_id' => 'new-device',
    ]);
});
