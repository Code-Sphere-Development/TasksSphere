<div class="p-4 sm:p-6 lg:p-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="max-w-5xl mx-auto">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ __('Listen') }}</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    <a href="{{ route('dashboard') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">{{ __('Aufgaben') }}</a>
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <button type="button" wire:click="showCreateForm" class="inline-flex items-center px-4 py-2 border border-transparent rounded-full shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Neue Liste') }}
                </button>
            </div>
        </div>

        <!-- Create/Edit Form -->
        @if($showForm || $isEditing)
        <div class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden mb-10 transition-all border border-gray-100 dark:border-gray-700">
            <div class="p-6 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <span class="bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 p-2 rounded-lg mr-3">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </span>
                    {{ $isEditing ? __('Liste bearbeiten') : __('Neue Liste erstellen') }}
                </h2>

                <form wire:submit.prevent="{{ $isEditing ? 'updateList' : 'createList' }}" class="space-y-6">
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Titel') }}</label>
                        <input type="text" id="title" wire:model="title" placeholder="{{ __('z.B. Einkaufsliste') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors sm:text-sm p-3">
                        @error('title') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Beschreibung (optional)') }}</label>
                        <textarea id="description" wire:model="description" rows="2" placeholder="{{ __('Weitere Informationen...') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors sm:text-sm p-3"></textarea>
                    </div>

                    <!-- Type Selector (Radio Cards) -->
                    @if(!$isEditing)
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ __('Listentyp') }}</label>
                        <div class="grid grid-cols-2 gap-4">
                            <label class="relative flex items-center p-4 rounded-xl border-2 cursor-pointer transition-all {{ $type === 'checklist' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                <input type="radio" wire:model.live="type" value="checklist" class="sr-only">
                                <div class="flex items-center space-x-3">
                                    <span class="text-2xl">&#10003;</span>
                                    <div>
                                        <span class="block text-sm font-bold text-gray-900 dark:text-white">{{ __('Checkliste') }}</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Einfache Abhak-Liste') }}</span>
                                    </div>
                                </div>
                            </label>
                            <label class="relative flex items-center p-4 rounded-xl border-2 cursor-pointer transition-all {{ $type === 'tasks' ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                <input type="radio" wire:model.live="type" value="tasks" class="sr-only">
                                <div class="flex items-center space-x-3">
                                    <span class="text-2xl">&#128203;</span>
                                    <div>
                                        <span class="block text-sm font-bold text-gray-900 dark:text-white">{{ __('Aufgaben-Liste') }}</span>
                                        <span class="block text-xs text-gray-500 dark:text-gray-400">{{ __('Mit Datum & Details') }}</span>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                    @endif

                    <!-- Emoji Picker -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ __('Symbol (optional)') }}</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach(['&#128203;', '&#128221;', '&#128218;', '&#127919;', '&#11088;', '&#10084;&#65039;', '&#127968;', '&#128176;', '&#127828;', '&#9992;&#65039;', '&#128170;', '&#127911;', '&#128218;', '&#128640;', '&#127793;', '&#128161;', '&#127881;', '&#128736;&#65039;', '&#128293;', '&#128142;'] as $emoji)
                                <button type="button" wire:click="$set('icon', '{{ $emoji }}')" class="w-10 h-10 flex items-center justify-center text-xl rounded-lg border-2 transition-all {{ $icon === $emoji ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 scale-110' : 'border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50' }}">
                                    {!! $emoji !!}
                                </button>
                            @endforeach
                            @if($icon)
                                <button type="button" wire:click="$set('icon', '')" class="w-10 h-10 flex items-center justify-center text-xs rounded-lg border-2 border-gray-200 dark:border-gray-700 hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-400 hover:text-red-500 transition-all">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Color Swatches -->
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">{{ __('Farbe (optional)') }}</label>
                        <div class="flex flex-wrap gap-2">
                            @foreach(['#EF4444', '#F97316', '#F59E0B', '#84CC16', '#22C55E', '#14B8A6', '#06B6D4', '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899', '#F43F5E'] as $swatch)
                                <button type="button" wire:click="$set('color', '{{ $swatch }}')" class="w-8 h-8 rounded-full border-2 transition-all {{ $color === $swatch ? 'border-gray-900 dark:border-white scale-125 ring-2 ring-offset-2 ring-blue-500' : 'border-transparent hover:scale-110' }}" style="background-color: {{ $swatch }}"></button>
                            @endforeach
                            @if($color)
                                <button type="button" wire:click="$set('color', '')" class="w-8 h-8 flex items-center justify-center rounded-full border-2 border-gray-200 dark:border-gray-700 hover:bg-red-50 dark:hover:bg-red-900/20 text-gray-400 hover:text-red-500 transition-all">
                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex flex-col md:flex-row justify-end space-y-3 md:space-y-0 md:space-x-3 pt-4">
                        <button type="button" wire:click="resetForm" class="inline-flex items-center justify-center px-8 py-3 border border-gray-300 dark:border-gray-600 text-base font-bold rounded-xl shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all">
                            {{ __('Abbrechen') }}
                        </button>
                        <button type="submit" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-bold rounded-xl shadow-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform hover:-translate-y-0.5">
                            {{ $isEditing ? __('Änderungen speichern') : __('Liste erstellen') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- My Lists -->
        <section class="mb-12">
            <h2 class="text-sm font-black uppercase tracking-widest text-blue-600 dark:text-blue-400 flex items-center px-2 mb-4">
                <span class="w-1.5 h-1.5 rounded-full bg-blue-500 mr-2"></span>
                {{ __('Meine Listen') }}
                <span class="ml-2 px-2 py-0.5 text-xs bg-blue-100 dark:bg-blue-900/30 rounded-full font-bold">
                    {{ $myLists->count() }}
                </span>
            </h2>

            @if($myLists->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($myLists as $list)
                        <div class="group bg-white dark:bg-gray-800 shadow-sm hover:shadow-md rounded-2xl overflow-hidden border border-gray-100 dark:border-gray-700 transition-all" style="border-left: 4px solid {{ $list->color ?? '#3B82F6' }}">
                            <a href="{{ route('lists.show', $list) }}" wire:navigate class="block p-5">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center space-x-3 min-w-0">
                                        @if($list->icon)
                                            <span class="text-2xl flex-shrink-0">{!! $list->icon !!}</span>
                                        @else
                                            <span class="flex-shrink-0 bg-gray-100 dark:bg-gray-700 w-10 h-10 rounded-lg flex items-center justify-center">
                                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <h3 class="text-base font-bold text-gray-900 dark:text-white truncate">{{ $list->title }}</h3>
                                            @if($list->description)
                                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $list->description }}</p>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="ml-2 flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                        {{ $list->itemCount() }}
                                    </span>
                                </div>
                            </a>
                            <div class="flex items-center justify-end px-5 pb-3 -mt-1 space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button wire:click="editList({{ $list->id }})" class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                </button>
                                <button wire:click="deleteList({{ $list->id }})" wire:confirm="{{ __('Liste wirklich löschen?') }}" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-3xl border-2 border-dashed border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="text-4xl mb-3">&#128203;</div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Noch keine Listen') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Erstelle deine erste Liste, um loszulegen.') }}</p>
                </div>
            @endif
        </section>

    </div>
</div>
