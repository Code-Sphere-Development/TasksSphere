<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('user can update fcm token', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/fcm-token', [
        'fcm_token' => 'test-token-123',
    ]);

    $response->assertStatus(200);
    $this->assertEquals('test-token-123', $user->fresh()->fcm_token);
});

test('fcm token update requires authentication', function () {
    $response = $this->postJson('/api/fcm-token', [
        'fcm_token' => 'test-token-123',
    ]);

    $response->assertStatus(401);
});
