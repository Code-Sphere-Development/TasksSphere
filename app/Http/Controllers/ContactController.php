<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactRequest;
use App\Mail\ContactMessage;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('contact');
    }

    public function send(ContactRequest $request): RedirectResponse
    {
        if (! $request->isFromBot()) {
            Mail::to(config('mail.contact.to'))->send(new ContactMessage(
                name: $request->string('name')->trim()->toString(),
                email: $request->string('email')->trim()->toString(),
                message: $request->string('message')->trim()->toString(),
            ));
        }

        return redirect()
            ->route('contact.show')
            ->with('status', __('Deine Nachricht ist angekommen. Wir melden uns per E-Mail.'));
    }
}
