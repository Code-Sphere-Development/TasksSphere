<?php

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('user can update fcm token with device id', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/fcm-token', [
            'fcm_token' => 'new-token-123',
            'device_id' => 'my-phone-uuid',
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('user_devices', [
        'user_id' => $user->id,
        'device_id' => 'my-phone-uuid',
        'fcm_token' => 'new-token-123',
    ]);

    $user->refresh();
    $this->assertEquals('new-token-123', $user->fcm_token);
});

test('user can update multiple devices', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-device')->plainTextToken;

    // First device
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/fcm-token', [
            'fcm_token' => 'token-phone',
            'device_id' => 'phone-id',
        ]);

    // Second device
    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson('/api/fcm-token', [
            'fcm_token' => 'token-tablet',
            'device_id' => 'tablet-id',
        ]);

    $this->assertEquals(2, $user->devices()->count());
    $this->assertEquals(['token-phone', 'token-tablet'], $user->devices()->pluck('fcm_token')->sort()->values()->toArray());
});

test('token is removed from other users when reassigned', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Token belongs to user1
    UserDevice::create([
        'user_id' => $user1->id,
        'fcm_token' => 'shared-token',
        'device_id' => 'common-device',
    ]);

    $token2 = $user2->createToken('test-device-2')->plainTextToken;

    // User2 claims the token
    $this->withHeader('Authorization', 'Bearer '.$token2)
        ->postJson('/api/fcm-token', [
            'fcm_token' => 'shared-token',
            'device_id' => 'common-device',
        ]);

    $this->assertDatabaseMissing('user_devices', ['user_id' => $user1->id, 'fcm_token' => 'shared-token']);
    $this->assertDatabaseHas('user_devices', ['user_id' => $user2->id, 'fcm_token' => 'shared-token']);
});
