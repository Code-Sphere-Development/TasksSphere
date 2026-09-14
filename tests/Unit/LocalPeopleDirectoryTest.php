<?php

use App\Models\Household;
use App\Models\User;
use App\Support\People\LocalPeopleDirectory;
use App\Support\People\PeopleDirectory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->directory = new LocalPeopleDirectory;
});

test('the container resolves the local directory by default', function () {
    expect(app(PeopleDirectory::class))->toBeInstanceOf(LocalPeopleDirectory::class);
});

test('a user without a household can only assign to themselves', function () {
    $user = User::factory()->create();

    $people = $this->directory->assignableFor($user);

    expect($people)->toHaveCount(1);
    expect($people->first()->id)->toBe($user->id);
});

test('household members are assignable to each other', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    Household::createFor($owner, 'Familie')->addMember($member);

    expect($this->directory->assignableFor($owner)->pluck('id'))
        ->toContain($owner->id, $member->id);
});

test('members of a foreign household are not assignable', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();
    Household::createFor($owner, 'Familie');
    Household::createFor($stranger, 'Andere Familie');

    expect($this->directory->assignableFor($owner)->pluck('id'))
        ->not->toContain($stranger->id);
});

test('canAssign follows the shared household', function () {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $stranger = User::factory()->create();
    Household::createFor($owner, 'Familie')->addMember($member);

    expect($this->directory->canAssign($owner, $member))->toBeTrue();
    expect($this->directory->canAssign($owner, $stranger))->toBeFalse();
});
