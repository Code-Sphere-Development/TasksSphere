<x-guest-layout>
    <div class="relative min-h-screen flex flex-col items-center justify-center selection:bg-indigo-500 selection:text-white">
        <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">
            <header class="flex flex-col items-center py-10">
                <x-application-logo class="w-48 h-48 mb-4" />
                <h1 class="text-5xl font-bold tracking-tight text-gray-900 dark:text-white sm:text-7xl">
                    TaskSphere
                </h1>
                <p class="mt-6 text-lg leading-8 text-gray-600 dark:text-gray-400 text-center max-w-2xl">
                    Organisiere deine Aufgaben effizient und behalte den Überblick mit TaskSphere.
                    Deine persönliche Zentrale für Produktivität.
                </p>

                <div class="mt-10 flex items-center justify-center gap-x-6">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/dashboard') }}" class="rounded-md bg-indigo-600 px-6 py-3 text-lg font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                Zum Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-md bg-indigo-600 px-6 py-3 text-lg font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                                Anmelden
                            </a>

                            <a href="{{ rtrim(config('codesphere.url'), '/') }}/register" class="text-lg font-semibold leading-6 text-gray-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition">
                                Registrieren <span aria-hidden="true">→</span>
                            </a>
                        @endauth
                    @endif
                </div>
            </header>

            <main class="mt-20">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <!-- Feature 1 -->
                    <div class="relative p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 text-center sm:text-left">
                        <div class="flex justify-center sm:justify-start text-indigo-600 dark:text-indigo-400 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Einfache Aufgabenverwaltung</h3>
                        <p class="text-gray-600 dark:text-gray-400">Erstelle, bearbeite und erledige Aufgaben mit nur wenigen Klicks.</p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="relative p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 text-center sm:text-left">
                        <div class="flex justify-center sm:justify-start text-indigo-600 dark:text-indigo-400 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Wiederkehrende Termine</h3>
                        <p class="text-gray-600 dark:text-gray-400">Verpasse nie wieder etwas dank flexibler Wiederholungsregeln.</p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="relative p-8 bg-white dark:bg-gray-800 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-700 text-center sm:text-left sm:col-span-2 lg:col-span-1">
                        <div class="flex justify-center sm:justify-start text-indigo-600 dark:text-indigo-400 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Überall verfügbar</h3>
                        <p class="text-gray-600 dark:text-gray-400">Deine Aufgaben sind sicher in der Cloud gespeichert und von überall abrufbar.</p>
                    </div>
                </div>
            </main>

            <footer class="py-16 text-center text-sm text-gray-500 dark:text-gray-400">
                &copy; {{ date('Y') }} TaskSphere. Alle Rechte vorbehalten.
            </footer>
        </div>
    </div>
</x-guest-layout>