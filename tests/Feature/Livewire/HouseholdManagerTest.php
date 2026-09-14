<?php

use App\Livewire\HouseholdManager;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the page requires authentication', function () {
    $this->get('/household')->assertRedirect('/login');
});

test('render shows only households the user belongs to', function () {
    $user = User::factory()->create();
    $mine = Household::createFor($user, 'Meine Familie');
    Household::createFor(User::factory()->create(), 'Fremde Familie');

    Livewire::actingAs($user)
        ->test(HouseholdManager::class)
        ->assertViewHas('households', fn ($h) => $h->pluck('id')->all() === [$mine->id])
        ->assertSee('Meine Familie')
        ->assertDontSee('Fremde Familie');
});

test('a household can be created and the creator becomes a member', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(HouseholdManager::class)
        ->set('name', 'Familie Muster')
        ->call('createHousehold');

    $household = Household::where('name', 'Familie Muster')->first();
    expect($household->owner_id)->toBe($user->id);
    expect($household->hasMember($user))->toBeTrue();
});

test('creating a household requires a name', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(HouseholdManager::class)
        ->set('name', '')
        ->call('createHousehold')
        ->assertHasErrors(['name' => 'required']);
});

test('a member is added by email address', function () {
    $owner = User::factory()->create();
    $friend = User::factory()->create(['email' => 'freund@example.com']);
    $household = Household::createFor($owner, 'Familie');

    Livewire::actingAs($owner)
        ->test(HouseholdManager::class)
        ->set('inviteEmail', 'freund@example.com')
        ->call('addMember', $household->id);

    expect($household->fresh()->hasMember($friend))->toBeTrue();
});

test('an unknown email address is rejected with an error', function () {
    $owner = User::factory()->create();
    $household = Household::createFor($owner, 'Familie');

    Livewire::actingAs($owner)
        ->test(HouseholdManager::class)
        ->set('inviteEmail', 'niemand@example.com')
        ->call('addMember', $household->id)
        ->assertHasErrors('inviteEmail');

    expect($household->fresh()->members)->toHaveCount(1);
});

test('only the owner may add members', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    User::factory()->create(['email' => 'dritter@example.com']);
    $household = Household::createFor($owner, 'Familie');
    $household->addMember($member);

    Livewire::actingAs($member)
        ->test(HouseholdManager::class)
        ->set('inviteEmail', 'dritter@example.com')
        ->call('addMember', $household->id)
        ->assertForbidden();
});

test('the owner can remove a member', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $household = Household::createFor($owner, 'Familie');
    $household->addMember($member);

    Livewire::actingAs($owner)
        ->test(HouseholdManager::class)
        ->call('removeMember', $household->id, $member->id);

    expect($household->fresh()->hasMember($member))->toBeFalse();
});

test('a member cannot remove someone else', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $victim = User::factory()->create();
    $household = Household::createFor($owner, 'Familie');
    $household->addMember($member);
    $household->addMember($victim);

    Livewire::actingAs($member)
        ->test(HouseholdManager::class)
        ->call('removeMember', $household->id, $victim->id)
        ->assertForbidden();
});

test('a household the user does not belong to cannot be touched', function () {
    $outsider = User::factory()->create();
    $household = Household::createFor(User::factory()->create(), 'Fremde Familie');

    Livewire::actingAs($outsider)
        ->test(HouseholdManager::class)
        ->call('removeMember', $household->id, $household->owner_id)
        ->assertForbidden();
});
