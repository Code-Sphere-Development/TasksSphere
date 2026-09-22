<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the imprint is public', function () {
    $this->get('/impressum')
        ->assertOk()
        ->assertSee('Impressum')
        ->assertSee('Angaben gemäß § 5 DDG');
});

test('the privacy policy is public', function () {
    $this->get('/datenschutz')
        ->assertOk()
        ->assertSee('Datenschutzerklärung')
        ->assertSee('Firebase Cloud Messaging')
        ->assertSee('Bunny Fonts');
});

test('markdown is rendered, not shown raw', function () {
    $this->get('/impressum')
        ->assertSee('<h1>', false)
        ->assertDontSee('# Impressum');
});

test('the login page links to both pages', function () {
    $this->get('/login')
        ->assertSee(route('legal.imprint'))
        ->assertSee(route('legal.privacy'));
});

test('the dashboard links to both pages', function () {
    $this->actingAs(User::factory()->create())
        ->get('/dashboard')
        ->assertOk()
        ->assertSee(route('legal.imprint'))
        ->assertSee(route('legal.privacy'));
});

test('the legal pages link to each other and back home', function () {
    $this->get('/impressum')->assertSee(route('legal.privacy'));
    $this->get('/datenschutz')->assertSee(route('legal.imprint'));
});

test('line breaks inside the address are preserved', function () {
    $this->get('/impressum')
        ->assertSee('Mehlpfad 1b<br>', false)
        ->assertSee('40789 Monheim am Rhein', false);
});
