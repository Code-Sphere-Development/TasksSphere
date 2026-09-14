<?php

use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a household belongs to its owner', function () {
    $owner = User::factory()->create();
    $household = Household::factory()->for($owner, 'owner')->create();

    expect($household->owner->id)->toBe($owner->id);
});

test('the owner is a member of the household', function () {
    $owner = User::factory()->create();
    $household = Household::createFor($owner, 'Familie Muster');

    expect($household->members)->toHaveCount(1);
    expect($household->members->first()->id)->toBe($owner->id);
});

test('a member can be added and removed', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $household = Household::createFor($owner, 'Familie Muster');

    $household->addMember($other);
    expect($household->fresh()->members)->toHaveCount(2);

    $household->removeMember($other);
    expect($household->fresh()->members)->toHaveCount(1);
});

test('adding the same member twice does not duplicate the membership', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $household = Household::createFor($owner, 'Familie Muster');

    $household->addMember($other);
    $household->addMember($other);

    expect($household->fresh()->members)->toHaveCount(2);
});

test('a user can belong to several households', function () {
    $user = User::factory()->create();
    $a = Household::createFor(User::factory()->create(), 'A');
    $b = Household::createFor(User::factory()->create(), 'B');

    $a->addMember($user);
    $b->addMember($user);

    expect($user->households)->toHaveCount(2);
});

test('the owner cannot be removed from their own household', function () {
    $owner = User::factory()->create();
    $household = Household::createFor($owner, 'Familie Muster');

    $household->removeMember($owner);

    expect($household->fresh()->members)->toHaveCount(1);
});
