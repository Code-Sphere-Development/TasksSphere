<x-guest-layout>
    <div class="min-h-screen flex flex-col items-center justify-center px-6">
        <x-application-logo class="w-24 h-24 mb-6" />

        <div class="w-full max-w-md bg-white dark:bg-gray-800 shadow sm:rounded-lg p-6">
            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                Anmeldung fehlgeschlagen
            </h1>

            <p class="mt-3 text-sm text-red-600 dark:text-red-400" role="alert">
                {{ $error }}
            </p>

            <div class="mt-6 flex flex-col gap-3">
                @if ($switchAccountUrl)
                    <a href="{{ $switchAccountUrl }}"
                       class="inline-flex justify-center items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white transition ease-in-out duration-150">
                        Mit anderem Konto anmelden
                    </a>
                @endif

                <a href="{{ route('codesphere.redirect') }}"
                   class="inline-flex justify-center items-center px-4 py-2 border border-gray-300 dark:border-gray-500 rounded-md font-semibold text-xs text-gray-700 dark:text-gray-300 uppercase tracking-widest hover:bg-gray-50 dark:hover:bg-gray-700 transition ease-in-out duration-150">
                    Erneut versuchen
                </a>
            </div>
        </div>
    </div>
</x-guest-layout>
