<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('middleware creates device from headers when authenticated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/', [
            'X-FCM-Token' => 'header-token-123',
            'X-Device-ID' => 'header-device-id',
        ]);

    $this->assertDatabaseHas('user_devices', [
        'user_id' => $user->id,
        'fcm_token' => 'header-token-123',
        'device_id' => 'header-device-id',
    ]);

    $this->assertEquals('header-token-123', $user->fresh()->fcm_token);
});

test('middleware updates existing device from headers', function () {
    $user = User::factory()->create();
    $user->devices()->create([
        'device_id' => 'header-device-id',
        'fcm_token' => 'old-token',
    ]);

    $this->actingAs($user)
        ->get('/', [
            'X-FCM-Token' => 'new-token',
            'X-Device-ID' => 'header-device-id',
        ]);

    $this->assertDatabaseHas('user_devices', [
        'user_id' => $user->id,
        'device_id' => 'header-device-id',
        'fcm_token' => 'new-token',
    ]);

    $this->assertDatabaseCount('user_devices', 1);
});

test('middleware does nothing when not authenticated', function () {
    $this->get('/', [
        'X-FCM-Token' => 'header-token-123',
        'X-Device-ID' => 'header-device-id',
    ]);

    $this->assertDatabaseMissing('user_devices', [
        'fcm_token' => 'header-token-123',
    ]);
});
