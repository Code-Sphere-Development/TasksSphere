{{-- Fusszeile mit den Pflichtangaben. Erscheint auf jeder Seite, angemeldet wie nicht. --}}
<footer class="mt-12 py-6 text-center text-xs text-gray-500 dark:text-gray-400">
    <nav class="inline-flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
        <a href="{{ route('legal.imprint') }}" class="hover:text-gray-900 dark:hover:text-white hover:underline">{{ __('Impressum') }}</a>
        <a href="{{ route('legal.privacy') }}" class="hover:text-gray-900 dark:hover:text-white hover:underline">{{ __('Datenschutz') }}</a>
        <a href="{{ route('contact.show') }}" class="hover:text-gray-900 dark:hover:text-white hover:underline">{{ __('Kontakt') }}</a>
    </nav>
</footer>
