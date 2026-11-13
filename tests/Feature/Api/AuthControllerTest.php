<?php

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| register / forgotPassword
|--------------------------------------------------------------------------
*/

test('register points to AccountSphere instead of creating an account', function () {
    $response = $this->postJson('/api/register', [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'device_name' => 'mobile-app',
    ]);

    $response->assertStatus(410)
        ->assertJson(['url' => 'https://account.code-sphere.de/register']);

    $this->assertDatabaseCount('users', 0);
});

test('forgot password points to AccountSphere', function () {
    $this->postJson('/api/forgot-password', ['email' => 'jane@example.com'])
        ->assertStatus(410)
        ->assertJson(['url' => 'https://account.code-sphere.de/forgot-password']);
});

/*
|--------------------------------------------------------------------------
| login
|--------------------------------------------------------------------------
*/

test('login checks the credentials against AccountSphere and returns a token', function () {
    fakeAccountSphereLogin(['id' => 42, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'language' => 'en']);

    $response = $this->postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'secret123',
        'device_name' => 'mobile-app',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'token',
            'user' => ['id', 'first_name', 'last_name', 'email', 'name', 'profile_photo_url'],
        ])
        ->assertJsonPath('user.first_name', 'Jane')
        ->assertJsonPath('user.last_name', 'Doe')
        ->assertJsonPath('user.email', 'jane@example.com')
        ->assertJsonMissingPath('user.access_token');

    $user = User::where('codesphere_id', 42)->sole();
    expect($user->language)->toBe('en');

    $this->assertDatabaseHas('personal_access_tokens', [
        'tokenable_id' => $user->id,
        'name' => 'mobile-app',
    ]);

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/auth/login')
        && $request['email'] === 'jane@example.com'
        && $request['password'] === 'secret123');
});

test('login revokes the AccountSphere token it does not need', function () {
    fakeAccountSphereLogin(['id' => 42, 'name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $this->postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'secret123',
        'device_name' => 'mobile-app',
    ])->assertStatus(200);

    Http::assertSent(fn ($request) => str_ends_with($request->url(), '/api/auth/logout')
        && $request->hasHeader('Authorization', 'Bearer as-token'));
});

test('login lands in a linked existing account', function () {
    $user = User::factory()->create(['codesphere_id' => 42, 'first_name' => 'Alt']);
    fakeAccountSphereLogin(['id' => 42, 'name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $response = $this->postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'secret123',
        'device_name' => 'gehirn-agent',
    ]);

    $response->assertStatus(200)->assertJsonPath('user.id', $user->id);
    expect($user->fresh()->first_name)->toBe('Jane');
    $this->assertDatabaseCount('users', 1);
});

test('login rejects invalid credentials', function () {
    fakeAccountSphereLogin([], 401);

    $response = $this->postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'wrong-password',
        'device_name' => 'mobile-app',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);

    $this->assertDatabaseCount('users', 0);
});

test('login rejects an account deactivated in AccountSphere', function () {
    fakeAccountSphereLogin([], 403);

    $this->postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'secret123',
        'device_name' => 'mobile-app',
    ])->assertStatus(422)->assertJsonValidationErrors(['email']);
});

test('login passes AccountSphere throttling through', function () {
    fakeAccountSphereLogin([], 429);

    $this->postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'secret123',
        'device_name' => 'mobile-app',
    ])->assertStatus(429);
});

test('login answers 503 when AccountSphere is down', function () {
    Http::fake(['*/api/auth/login' => Http::response('Bad Gateway', 502)]);

    $this->postJson('/api/login', [
        'email' => 'jane@example.com',
        'password' => 'secret123',
        'device_name' => 'mobile-app',
    ])->assertStatus(503);

    $this->assertDatabaseCount('users', 0);
});

test('login requires email, password and device name', function () {
    Http::fake();

    $response = $this->postJson('/api/login', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email', 'password', 'device_name']);

    Http::assertNothingSent();
});

test('login updates the timezone from the header', function () {
    $user = User::factory()->create(['codesphere_id' => 42, 'timezone' => 'UTC']);
    fakeAccountSphereLogin(['id' => 42, 'name' => 'Jane Doe', 'email' => 'jane@example.com']);

    $response = $this->postJson('/api/login', [
        'email' => 'jane@example.com',
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
