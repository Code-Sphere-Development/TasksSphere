<x-guest-layout>
    <x-slot name="title">{{ __('Kontakt') }}</x-slot>

    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 py-10 px-4">
        <div class="mx-auto max-w-xl">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white">
                <x-application-mark class="h-8 w-8" />
                TasksSphere
            </a>

            <div class="mt-6 bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm p-6 sm:p-10">
                <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">{{ __('Kontakt') }}</h1>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Schreib uns, wir antworten per E-Mail an die Adresse, die du hier angibst.') }}
                </p>

                @if(session('status'))
                    <div class="mt-6 rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/30 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-300" role="status">
                        {{ session('status') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('contact.send') }}" class="mt-6 space-y-5">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Name') }}</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name"
                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm p-3">
                        @error('name') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('E-Mail-Adresse') }}</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email"
                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm p-3">
                        @error('email') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="message" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Nachricht') }}</label>
                        <textarea id="message" name="message" rows="6" required
                                  class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm p-3">{{ old('message') }}</textarea>
                        @error('message') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    </div>

                    {{-- Honigtopf: fuer Menschen unsichtbar, Bots fuellen ihn. Nicht per
                         display:none, das erkennen manche; stattdessen aus dem Sichtfeld. --}}
                    <div class="absolute -left-[9999px] top-0 h-px w-px overflow-hidden" aria-hidden="true">
                        <label for="website">{{ __('Website') }}</label>
                        <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                    </div>

                    <p class="text-xs text-gray-500 dark:text-gray-400">
                        {{ __('Deine Angaben werden nur zur Beantwortung deiner Anfrage verwendet.') }}
                        <a href="{{ route('legal.privacy') }}" class="underline hover:text-gray-900 dark:hover:text-white">{{ __('Datenschutz') }}</a>
                    </p>

                    <button type="submit" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-bold rounded-xl shadow-sm text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all">
                        {{ __('Nachricht senden') }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-guest-layout>
