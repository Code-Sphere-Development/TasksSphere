<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-8">

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">{{ __('Haushalte') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Wer hier Mitglied ist, kann Aufgaben zugewiesen bekommen.') }}
                </p>
            </div>
            <a href="{{ route('dashboard') }}" class="text-sm font-medium text-brand-600 dark:text-brand-400 hover:underline">
                {{ __('Zu den Aufgaben') }}
            </a>
        </div>

        <form wire:submit="createHousehold" class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
            <label for="household-name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">
                {{ __('Neuer Haushalt') }}
            </label>
            <div class="mt-2 flex flex-col sm:flex-row gap-3">
                <input id="household-name" type="text" wire:model="name" placeholder="{{ __('Name des Haushalts') }}"
                       class="flex-grow border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm p-3">
                <button type="submit" class="inline-flex justify-center items-center px-6 py-3 border border-transparent text-sm font-bold rounded-xl shadow-sm text-white bg-brand-600 hover:bg-brand-700 transition-all">
                    {{ __('Anlegen') }}
                </button>
            </div>
            @error('name') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
        </form>

        @forelse($households as $household)
            @php($isOwner = $household->owner_id === auth()->id())
            <section class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white break-words">{{ $household->name }}</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ __('Verwaltet von :name', ['name' => $household->owner?->name ?? '—']) }}
                        </p>
                    </div>
                    @unless($isOwner)
                        <button type="button" wire:click="leaveHousehold({{ $household->id }})"
                                wire:confirm="{{ __('Diesen Haushalt wirklich verlassen?') }}"
                                class="flex-shrink-0 text-sm font-medium text-gray-500 hover:text-red-600 dark:text-gray-400 dark:hover:text-red-400">
                            {{ __('Verlassen') }}
                        </button>
                    @endunless
                </div>

                <ul class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($household->members as $member)
                        <li class="py-2 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $member->name }}</span>
                                @if($member->id === $household->owner_id)
                                    <span class="ml-2 text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-400">
                                        {{ __('Besitzer') }}
                                    </span>
                                @endif
                                <span class="block text-xs text-gray-500 dark:text-gray-400 truncate">{{ $member->email }}</span>
                            </div>
                            @if($isOwner && $member->id !== $household->owner_id)
                                <button type="button" wire:click="removeMember({{ $household->id }}, {{ $member->id }})"
                                        class="flex-shrink-0 p-1.5 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg transition-colors"
                                        title="{{ __('Entfernen') }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if($isOwner)
                    <form wire:submit="addMember({{ $household->id }})" class="mt-4 flex flex-col sm:flex-row gap-3">
                        <input type="email" wire:model="inviteEmail" placeholder="{{ __('E-Mail-Adresse') }}"
                               class="flex-grow border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 sm:text-sm p-3">
                        <button type="submit" class="inline-flex justify-center items-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-sm font-bold rounded-xl shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all">
                            {{ __('Hinzufügen') }}
                        </button>
                    </form>
                    @error('inviteEmail') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                    <p class="mt-2 text-xs text-gray-400">{{ __('Die Person braucht bereits ein Konto.') }}</p>
                @endif
            </section>
        @empty
            <div class="text-center py-12 bg-white dark:bg-gray-800 rounded-2xl border border-dashed border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Noch kein Haushalt') }}</h3>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Lege einen an, um Aufgaben zu verteilen.') }}</p>
            </div>
        @endforelse
    </div>
</div>
