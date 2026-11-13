<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div>
        <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
            <x-action-section>
                <x-slot name="title">
                    {{ __('Profile Information') }}
                </x-slot>

                <x-slot name="description">
                    Name, E-Mail, Passwort und Sprache verwaltest du zentral in deinem CodeSphere-Konto.
                </x-slot>

                <x-slot name="content">
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Name') }}</dt>
                            <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ Auth::user()->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('Email') }}</dt>
                            <dd class="mt-1 text-gray-900 dark:text-gray-100">{{ Auth::user()->email }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6">
                        <a href="{{ rtrim(config('codesphere.url'), '/') }}/profile"
                           class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white transition ease-in-out duration-150">
                            In CodeSphere bearbeiten
                        </a>
                    </div>
                </x-slot>
            </x-action-section>

            <x-section-border />

            <div class="mt-10 sm:mt-0">
                <livewire:profile.update-notification-preferences-form />
            </div>

            <x-section-border />

            <div class="mt-10 sm:mt-0">
                <livewire:delete-account-form />
            </div>
        </div>
    </div>
</x-app-layout>
