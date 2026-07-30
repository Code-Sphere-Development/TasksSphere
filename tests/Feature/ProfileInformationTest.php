<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('current profile information is available', function () {
    $this->actingAs($user = User::factory()->create());

    $component = Livewire::test(UpdateProfileInformationForm::class);

    $this->assertEquals($user->first_name, $component->state['first_name']);
    $this->assertEquals($user->last_name, $component->state['last_name']);
    $this->assertEquals($user->email, $component->state['email']);
});

test('profile information can be updated', function () {
    $this->actingAs($user = User::factory()->create());

    Livewire::test(UpdateProfileInformationForm::class)
        ->set('state', [
            'first_name' => 'Test',
            'last_name' => 'Name',
            'email' => 'test@example.com',
            'language' => 'de',
        ])
        ->call('updateProfileInformation');

    $this->assertEquals('Test', $user->fresh()->first_name);
    $this->assertEquals('Name', $user->fresh()->last_name);
    $this->assertEquals('test@example.com', $user->fresh()->email);
});
