<?php

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

/**
 * Spielt den Rueckweg von AccountSphere nach /auth/callback durch.
 *
 * @param  array<string, mixed>  $profile  der user-Teil von /api/user/profile
 */
function completeCodeSphereLogin($test, array $profile)
{
    Http::fake([
        '*/oauth/token' => Http::response([
            'access_token' => 'access',
            'refresh_token' => 'refresh',
            'expires_in' => 3600,
        ]),
        '*/api/user/profile' => Http::response(['user' => $profile, 'groups' => []]),
    ]);

    return $test->withSession(['codesphere_oauth_state' => 'state-123'])
        ->get('/auth/callback?code=abc&state=state-123');
}

test('the login route starts the AccountSphere flow', function () {
    $this->get('/login')->assertRedirect(route('codesphere.redirect'));

    $this->get('/auth/redirect')
        ->assertRedirectContains('https://account.code-sphere.de/oauth/authorize');
});

test('a failed login shows the error instead of looping back to AccountSphere', function () {
    config(['codesphere.allowed_account_types' => ['private'], 'codesphere.client_id' => 'client-1']);

    $this->withHeader('Accept-Language', 'de');

    completeCodeSphereLogin($this, [
        'id' => 7,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'language' => 'de',
        'account_type' => 'business',
    ])->assertRedirect(route('login'));

    $this->assertGuest();

    $this->followingRedirects()
        ->get('/login')
        ->assertOk()
        ->assertSee('Mit diesem Kontotyp kannst du TasksSphere nicht nutzen.')
        ->assertSee(route('codesphere.redirect'))
        ->assertSee('https://account.code-sphere.de/oauth/logout?client_id=client-1&amp;post_logout_redirect_uri='.urlencode(route('login')), false);
});

test('protected pages send guests to the login', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

test('the first login creates a local account with a snapshot of the profile', function () {
    completeCodeSphereLogin($this, [
        'id' => 7,
        'name' => 'Jane Mary Doe',
        'email' => 'jane@example.com',
        'language' => 'en',
    ])->assertRedirect(route('dashboard'));

    $user = User::where('codesphere_id', 7)->sole();

    $this->assertAuthenticatedAs($user);
    expect($user->only('first_name', 'last_name', 'email', 'language'))->toBe([
        'first_name' => 'Jane',
        'last_name' => 'Mary Doe',
        'email' => 'jane@example.com',
        'language' => 'en',
    ]);
});

test('a linked existing account keeps its tasks', function () {
    $user = User::factory()->create(['codesphere_id' => 7]);
    $task = Task::factory()->create(['user_id' => $user->id]);

    completeCodeSphereLogin($this, ['id' => 7, 'name' => 'Jane Doe', 'email' => 'jane@example.com', 'language' => 'de']);

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseCount('users', 1);
    expect($task->fresh()->user_id)->toBe($user->id);
});

test('a forged state is rejected', function () {
    Http::fake();

    $this->withSession(['codesphere_oauth_state' => 'state-123'])
        ->get('/auth/callback?code=abc&state=forged');

    $this->assertGuest();
    Http::assertNothingSent();
});

test('logout ends the local session and the AccountSphere session', function () {
    config(['codesphere.client_id' => 'client-1']);

    $this->actingAs(User::factory()->create())
        ->post('/auth/logout')
        ->assertRedirectContains('https://account.code-sphere.de/oauth/logout');

    $this->assertGuest();
});
