<?php

use App\Models\Task;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| Stille Geräte
|--------------------------------------------------------------------------
|
| Ein Gerät, das sich seit Monaten nicht mehr beim Server gemeldet hat, darf
| keine Pushes mehr bekommen. Die App kann ihren Auth-Token lokal verloren
| haben (z. B. durch ein TestFlight-Update), ohne dass der Server davon
| erfährt: Der FCM-Token bleibt gültig, also käme der Push weiterhin an.
|
*/

test('routeNotificationForFcm leaves out devices that have not been seen within the threshold', function () {
    config()->set('tasks.device_stale_after_days', 180);

    $user = User::factory()->create();

    UserDevice::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'still-in-use',
        'fcm_token' => 'frischer-token',
        'last_seen_at' => now()->subDays(3),
    ]);

    UserDevice::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'long-silent',
        'fcm_token' => 'alter-token',
        'last_seen_at' => now()->subDays(181),
    ]);

    expect($user->routeNotificationForFcm())->toBe(['frischer-token']);
});

test('routeNotificationForFcm treats a device that was never seen as silent', function () {
    $user = User::factory()->create();

    UserDevice::factory()->create([
        'user_id' => $user->id,
        'fcm_token' => 'token-ohne-zeitstempel',
        'last_seen_at' => null,
    ]);

    expect($user->routeNotificationForFcm())->toBe([]);
});

test('the threshold is configurable', function () {
    config()->set('tasks.device_stale_after_days', 7);

    $user = User::factory()->create();

    UserDevice::factory()->create([
        'user_id' => $user->id,
        'fcm_token' => 'zehn-tage-alt',
        'last_seen_at' => now()->subDays(10),
    ]);

    expect($user->routeNotificationForFcm())->toBe([]);

    config()->set('tasks.device_stale_after_days', 30);

    expect($user->fresh()->routeNotificationForFcm())->toBe(['zehn-tage-alt']);
});

test('reminder command does not push to a device that has gone silent', function () {
    Notification::fake();
    config()->set('tasks.device_stale_after_days', 180);

    $user = User::factory()->create();
    UserDevice::factory()->create([
        'user_id' => $user->id,
        'fcm_token' => 'alter-token',
        'last_seen_at' => now()->subDays(181),
    ]);

    $task = Task::factory()->create([
        'user_id' => $user->id,
        'due_at' => now()->subMinute(),
        'completed_at' => null,
        'last_notified_at' => null,
    ]);

    $this->artisan('tasks:send-reminders')->assertExitCode(0);

    Notification::assertNothingSent();

    // Die Aufgabe wird trotzdem als benachrichtigt markiert, damit der
    // Scheduler nicht bei jedem Lauf erneut über sie stolpert.
    expect($task->fresh()->last_notified_at)->not->toBeNull();
});

test('a newly registered device counts as seen right away', function () {
    $user = User::factory()->create();

    $device = UserDevice::create([
        'user_id' => $user->id,
        'device_id' => 'neu',
        'fcm_token' => 'neuer-token',
    ]);

    expect($device->last_seen_at)->not->toBeNull()
        ->and($user->routeNotificationForFcm())->toBe(['neuer-token']);
});

test('updateFcmToken refreshes the last seen timestamp of a silent device', function () {
    $user = User::factory()->create();

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'wieder-da',
        'fcm_token' => 'gleicher-token',
        'last_seen_at' => now()->subDays(200),
    ]);

    $user->updateFcmToken('gleicher-token', 'wieder-da');

    expect($device->fresh()->last_seen_at->isToday())->toBeTrue()
        ->and($user->routeNotificationForFcm())->toBe(['gleicher-token']);
});

test('an authenticated request carrying the fcm header refreshes the timestamp', function () {
    $user = User::factory()->create();

    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'geraet-1',
        'fcm_token' => 'header-token',
        'last_seen_at' => now()->subDays(200),
    ]);

    $this->actingAs($user)
        ->withHeaders([
            'X-FCM-Token' => 'header-token',
            'X-Device-ID' => 'geraet-1',
        ])
        ->getJson('/api/tasks')
        ->assertOk();

    expect($device->fresh()->last_seen_at->isToday())->toBeTrue();
});

test('the timestamp is not rewritten on every single request', function () {
    $user = User::factory()->create();

    $seenAt = now()->subMinutes(5);
    $device = UserDevice::factory()->create([
        'user_id' => $user->id,
        'device_id' => 'geraet-1',
        'fcm_token' => 'header-token',
        'last_seen_at' => $seenAt,
    ]);

    $user->updateFcmToken('header-token', 'geraet-1');

    // Innerhalb des Drosselfensters bleibt der Zeitstempel stehen, damit nicht
    // jeder API-Aufruf der App einen Schreibzugriff auslöst.
    expect($device->fresh()->last_seen_at->timestamp)->toBe($seenAt->timestamp);
});
