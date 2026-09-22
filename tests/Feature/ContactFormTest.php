<?php

use App\Mail\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function () {
    Mail::fake();
    config(['mail.contact.to' => 'betreiber@example.org']);
    RateLimiter::clear('contact|127.0.0.1');
});

const VALID = [
    'name' => 'Mara Ilgner',
    'email' => 'mara@example.org',
    'message' => 'Hallo, ich habe eine Frage zur Anwendung.',
    'website' => '',
];

test('the contact page is public', function () {
    $this->get('/kontakt')
        ->assertOk()
        ->assertSee(route('contact.send'))
        ->assertSee('name="website"', false);
});

test('the contact page is translated', function () {
    $this->withHeader('Accept-Language', 'de')->get('/kontakt')->assertSee('Nachricht senden');
    $this->withHeader('Accept-Language', 'en')->get('/kontakt')->assertSee('Send message');
});

test('a valid message is sent to the operator', function () {
    $this->post('/kontakt', VALID)
        ->assertRedirect('/kontakt')
        ->assertSessionHas('status');

    Mail::assertSent(ContactMessage::class, function (ContactMessage $mail) {
        return $mail->hasTo('betreiber@example.org')
            && $mail->hasReplyTo('mara@example.org')
            && $mail->name === 'Mara Ilgner';
    });
});

test('the sender gets no copy and the operator address is not leaked', function () {
    $this->post('/kontakt', VALID);

    Mail::assertSent(ContactMessage::class, fn ($mail) => ! $mail->hasTo('mara@example.org'));
    $this->get('/kontakt')->assertDontSee('betreiber@example.org');
});

test('name email and message are required', function () {
    $this->post('/kontakt', ['name' => '', 'email' => '', 'message' => ''])
        ->assertSessionHasErrors(['name', 'email', 'message']);

    Mail::assertNothingSent();
});

test('the email address must be valid', function () {
    $this->post('/kontakt', [...VALID, 'email' => 'keine-adresse'])
        ->assertSessionHasErrors('email');

    Mail::assertNothingSent();
});

test('a filled honeypot swallows the message without telling the bot', function () {
    $this->post('/kontakt', [...VALID, 'website' => 'http://spam.example'])
        ->assertRedirect('/kontakt')
        ->assertSessionHas('status');

    Mail::assertNothingSent();
});

test('too many messages in a row are throttled', function () {
    foreach (range(1, 5) as $i) {
        $this->post('/kontakt', VALID)->assertRedirect();
    }

    $this->post('/kontakt', VALID)->assertStatus(429);
});

test('the footer and the imprint link to the contact page', function () {
    $this->get('/login')->assertSee(route('contact.show'));
    $this->get('/impressum')->assertSee(route('contact.show'));
});
