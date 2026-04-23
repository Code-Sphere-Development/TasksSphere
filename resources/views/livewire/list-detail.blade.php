<div class="p-4 sm:p-6 lg:p-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="max-w-3xl mx-auto">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('lists.index') }}" wire:navigate class="inline-flex items-center text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors mb-4">
                <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                {{ __('Alle Listen') }}
            </a>
            <div class="flex items-center space-x-3">
                @if($taskList->icon)
                    <span class="text-3xl">{!! $taskList->icon !!}</span>
                @endif
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ $taskList->title }}</h1>
                    @if($taskList->description)
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $taskList->description }}</p>
                    @endif
                </div>
            </div>
        </div>

        @if($taskList->isChecklist())
            {{-- ==================== CHECKLIST VIEW ==================== --}}

            <!-- Active Items -->
            <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                <div class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach($activeItems as $item)
                        <div class="group flex items-start px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-all">
                            @if($editingItemId === $item->id)
                                {{-- Inline Edit --}}
                                <div class="w-full py-1">
                                    <form wire:submit.prevent="saveEditItem" class="space-y-3">
                                        <input type="text" wire:model="editingItemTitle" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors sm:text-sm p-3" autofocus>
                                        @error('editingItemTitle') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                        <textarea wire:model="editingItemNote" rows="2" placeholder="{{ __('Notiz (optional)') }}" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors sm:text-sm p-3"></textarea>
                                        <div class="flex space-x-2">
                                            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-bold rounded-xl text-white bg-blue-600 hover:bg-blue-700 transition-all">
                                                {{ __('Speichern') }}
                                            </button>
                                            <button type="button" wire:click="cancelEditItem" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-bold rounded-xl text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all">
                                                {{ __('Abbrechen') }}
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            @else
                                {{-- Normal Item --}}
                                <button wire:click="toggleItem({{ $item->id }})" class="flex-shrink-0 mt-0.5 h-6 w-6 rounded-full border-2 border-gray-300 dark:border-gray-600 hover:border-blue-500 dark:hover:border-blue-400 flex items-center justify-center transition-all">
                                </button>
                                <div class="ml-3 flex-grow min-w-0 cursor-pointer" wire:click="startEditItem({{ $item->id }})">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->title }}</span>
                                    @if($item->note)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate mt-0.5">{{ $item->note }}</p>
                                    @endif
                                </div>
                                <button wire:click="deleteItem({{ $item->id }})" class="flex-shrink-0 ml-2 p-1.5 text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 rounded-lg opacity-0 group-hover:opacity-100 transition-all">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Add Item Inline --}}
                <form wire:submit.prevent="addItem" class="flex items-center px-5 py-3 border-t border-gray-100 dark:border-gray-700">
                    <span class="flex-shrink-0 h-6 w-6 rounded-full border-2 border-dashed border-gray-300 dark:border-gray-600 flex items-center justify-center text-gray-400">
                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    </span>
                    <input type="text" wire:model="newItemTitle" placeholder="{{ __('Eintrag hinzufügen...') }}" class="ml-3 flex-grow border-0 bg-transparent text-sm text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-0 p-0" wire:keydown.enter="addItem">
                </form>
            </div>

            {{-- Completed Items --}}
            @if($completedItems->count() > 0)
                <div class="mt-6">
                    <button wire:click="$toggle('showCompletedItems')" class="flex items-center space-x-2 text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 transition-colors mb-3 px-1">
                        <svg class="h-4 w-4 transition-transform {{ $showCompletedItems ? 'rotate-90' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        <span>{{ __('Erledigt') }}</span>
                        <span class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-gray-700 rounded-full font-bold">{{ $completedItems->count() }}</span>
                    </button>

                    @if($showCompletedItems)
                        <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($completedItems as $item)
                                    <div class="group flex items-center px-5 py-3 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-all">
                                        <button wire:click="toggleItem({{ $item->id }})" class="flex-shrink-0 h-6 w-6 rounded-full bg-blue-500 flex items-center justify-center transition-all">
                                            <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                        </button>
                                        <span class="ml-3 flex-grow text-sm text-gray-400 dark:text-gray-500 line-through">{{ $item->title }}</span>
                                        <button wire:click="deleteItem({{ $item->id }})" class="flex-shrink-0 ml-2 p-1.5 text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 rounded-lg opacity-0 group-hover:opacity-100 transition-all">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-3 flex justify-end">
                            <button wire:click="clearCompleted" wire:confirm="{{ __('Alle erledigten Einträge löschen?') }}" class="text-sm font-medium text-red-500 hover:text-red-700 dark:hover:text-red-400 transition-colors">
                                {{ __('Erledigte löschen') }}
                            </button>
                        </div>
                    @endif
                </div>
            @endif

        @else
            {{-- ==================== TASK LIST VIEW ==================== --}}

            @if($assignedTasks->count() > 0)
                <div class="bg-white dark:bg-gray-800 shadow-sm rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden">
                    <div class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($assignedTasks as $task)
                            <div class="group flex items-center px-5 py-4 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-all">
                                <div class="flex-grow min-w-0">
                                    <div class="flex items-center space-x-2">
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate">{{ $task->title }}</h3>
                                        @if($task->isRecurring())
                                            <span class="text-purple-500 dark:text-purple-400 flex-shrink-0" title="{{ __('Wiederkehrend') }}">&#8635;</span>
                                        @endif
                                    </div>
                                    @if($task->due_at)
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ $task->due_at->translatedFormat('d.m.Y H:i') }} {{ __('Uhr') }}
                                        </p>
                                    @endif
                                </div>
                                <button wire:click="removeTask({{ $task->id }})" class="flex-shrink-0 ml-2 p-1.5 text-gray-300 dark:text-gray-600 hover:text-red-500 dark:hover:text-red-400 rounded-lg opacity-0 group-hover:opacity-100 transition-all" title="{{ __('Entfernen') }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-3xl border-2 border-dashed border-gray-100 dark:border-gray-700 shadow-sm">
                    <div class="bg-blue-50 dark:bg-blue-900/20 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Keine Aufgaben zugeordnet') }}</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Ordne Aufgaben dieser Liste zu.') }}</p>
                </div>
            @endif

            {{-- Assign Task Button + Picker --}}
            <div class="mt-6">
                <button wire:click="$toggle('showTaskPicker')" class="inline-flex items-center px-4 py-2 border border-transparent rounded-full shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Aufgabe zuordnen') }}
                </button>

                @if($showTaskPicker)
                    <div class="mt-3 bg-white dark:bg-gray-800 shadow-lg rounded-2xl border border-gray-100 dark:border-gray-700 overflow-hidden max-h-64 overflow-y-auto">
                        @if($availableTasks->count() > 0)
                            <div class="divide-y divide-gray-100 dark:divide-gray-700">
                                @foreach($availableTasks as $task)
                                    <button wire:click="assignTask({{ $task->id }})" class="w-full text-left px-5 py-3 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $task->title }}</span>
                                        @if($task->due_at)
                                            <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">{{ $task->due_at->translatedFormat('d.m.Y') }}</span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        @else
                            <div class="px-5 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                                {{ __('Keine verfügbaren Aufgaben') }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>
