<x-mail::message>
# {{ __('Kontaktanfrage') }}

**{{ __('Von') }}:** {{ $name }} ({{ $email }})

---

{{ $message }}

---

{{ __('Antworten gehen direkt an den Absender.') }}
</x-mail::message>
