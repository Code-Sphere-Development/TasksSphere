<div class="p-4 sm:p-6 lg:p-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="max-w-5xl mx-auto">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-extrabold text-gray-900 dark:text-white tracking-tight">{{ __('Aufgabenübersicht') }}</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    {{ __('Du hast heute :count offene Aufgaben.', ['count' => $todayCount]) }}
                </p>
            </div>
            <div class="mt-4 md:mt-0 flex gap-2">
                <a href="{{ route('lists.index') }}" wire:navigate class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-full text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    {{ __('Listen') }}
                </a>
                <button type="button" wire:click="showCreateForm" class="inline-flex items-center px-4 py-2 border border-transparent rounded-full shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('Neue Aufgabe') }}
                </button>
            </div>
        </div>

        <!-- Create/Edit Task Form -->
        @if($showForm || $isEditing)
        <div id="create-task-form" class="bg-white dark:bg-gray-800 shadow-xl rounded-2xl overflow-hidden mb-10 transition-all border border-gray-100 dark:border-gray-700">
            <div class="p-6 sm:p-8">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <span class="bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 p-2 rounded-lg mr-3">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </span>
                    {{ $isEditing ? __('Aufgabe bearbeiten') : __('Was steht an?') }}
                </h2>

                <form wire:submit.prevent="{{ $isEditing ? 'updateTask' : 'createTask' }}" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Titel der Aufgabe') }}</label>
                            <input type="text" id="title" wire:model="title" placeholder="{{ __('z.B. Wäsche waschen') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors sm:text-sm p-3">
                            @error('title') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Details (optional)') }}</label>
                            <textarea id="description" wire:model="description" rows="2" placeholder="{{ __('Weitere Informationen...') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors sm:text-sm p-3"></textarea>
                        </div>

                        <div x-show="!['daily', 'weekly'].includes($wire.frequency)">
                            <label for="due_at" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Datum & Uhrzeit') }}</label>
                            <div class="mt-1 relative">
                                <input type="datetime-local" id="due_at" wire:model="due_at" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors sm:text-sm p-3">
                            </div>
                            @error('due_at') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div x-show="['daily', 'weekly'].includes($wire.frequency)">
                            <label for="due_at_start" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Startet am (optional)') }}</label>
                            <div class="mt-1 relative">
                                <input type="date" id="due_at_start" wire:model="due_at" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors sm:text-sm p-3">
                            </div>
                            @error('due_at') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            <p class="mt-1 text-xs text-gray-500">{{ __('Standardmäßig heute.') }}</p>
                        </div>

                        <div>
                            <div>
                                <label for="frequency" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Wiederholung') }}</label>
                                <select id="frequency" wire:model.live="frequency" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors sm:text-sm p-3">
                                    <option value="none">{{ __('Einmalig') }}</option>
                                    <option value="hourly">{{ __('Stündlich') }}</option>
                                    <option value="daily">{{ __('Täglich') }}</option>
                                    <option value="weekly">{{ __('Wöchentlich') }}</option>
                                    <option value="monthly">{{ __('Monatlich') }}</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label for="recurrence_timezone" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Zeitzone') }}</label>
                            <select id="recurrence_timezone" wire:model.live="recurrence_timezone" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors sm:text-sm p-3">
                                @foreach(\DateTimeZone::listIdentifiers() as $tz)
                                    <option value="{{ $tz }}">{{ $tz }}</option>
                                @endforeach
                            </select>
                            @error('recurrence_timezone') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2" x-show="$wire.frequency === 'weekly'" x-transition>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Wochentage') }}</label>
                            <div class="flex flex-wrap gap-3">
                                @foreach([1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'] as $value => $label)
                                    <label class="relative flex items-center p-3 rounded-xl border border-gray-200 dark:border-gray-700 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                        <input type="checkbox" wire:model="weekdays" value="{{ $value }}" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __($label) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="$wire.frequency !== 'none'" x-transition>
                            <div class="md:col-span-2">
                                <label for="newTime" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Uhrzeit(en) für die Wiederholung hinzufügen') }}</label>
                                <div class="mt-1 flex space-x-2">
                                    <input type="time" id="newTime" wire:model="newTime" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 transition-colors sm:text-sm p-3">
                                    <button type="button" wire:click="addTime" class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-bold rounded-xl text-white bg-blue-600 hover:bg-blue-700 transition-all shadow-sm">
                                        {{ __('Hinzufügen') }}
                                    </button>
                                </div>
                                @error('newTime') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="md:col-span-2" x-show="$wire.times.length > 0" x-transition>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">{{ __('Festgelegte Uhrzeiten pro Intervall:') }}</label>
                            <div class="flex flex-wrap gap-2">
                                @foreach($times as $index => $time)
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-bold bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-200 border border-blue-200 dark:border-blue-800">
                                        {{ $time }} {{ __('Uhr') }}
                                        <button type="button" wire:click="removeTime({{ $index }})" class="ml-2 inline-flex items-center p-0.5 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-800 transition-colors">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col md:flex-row justify-end space-y-3 md:space-y-0 md:space-x-3 pt-4">
                        @if($showForm || $isEditing)
                            <button type="button" wire:click="cancelEdit" class="inline-flex items-center justify-center px-8 py-3 border border-gray-300 dark:border-gray-600 text-base font-bold rounded-xl shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all">
                                {{ __('Abbrechen') }}
                            </button>
                        @endif
                        <button type="submit" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-bold rounded-xl shadow-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all transform hover:-translate-y-0.5">
                            {{ $isEditing ? __('Änderungen speichern') : __('Aufgabe speichern') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <!-- Task List -->
        <div class="grid grid-cols-1 gap-12">
            <!-- Active Occurrences Grouped -->
            <section>
                @php
                    $activeOccurrences = $occurrences->filter(fn($o) => !$o['is_completed']);

                    $groups = [];

                    // 1. Überfällig
                    $overdue = $activeOccurrences->filter(fn($o) => $o['planned_at'] && $o['planned_at']->isPast() && !$o['planned_at']->isToday());
                    if ($overdue->count() > 0) {
                        $groups[] = ['title' => __('Überfällig'), 'tasks' => $overdue, 'color' => 'red'];
                    }

                    // 2. Pro Tag für die nächsten 7 Tage
                    for ($i = 0; $i <= 7; $i++) {
                        $date = now()->addDays($i);
                        $dayTasks = $activeOccurrences->filter(fn($o) => $o['planned_at'] && $o['planned_at']->isSameDay($date));

                        if ($dayTasks->count() > 0) {
                            $title = $date->isToday() ? __('Heute') : ($date->isTomorrow() ? __('Morgen') : $date->translatedFormat('l, d.m.'));
                            $color = $date->isToday() ? 'blue' : ($date->isTomorrow() ? 'indigo' : 'gray');
                            $groups[] = ['title' => $title, 'tasks' => $dayTasks, 'color' => $color];
                        }
                    }

                    // 3. Später (nach den 7 Tagen)
                    $later = $activeOccurrences->filter(fn($o) => $o['planned_at'] && $o['planned_at']->isAfter(now()->addDays(7)->endOfDay()));
                    if ($later->count() > 0) {
                        $groups[] = ['title' => __('Später'), 'tasks' => $later, 'color' => 'gray'];
                    }

                    // 4. Ohne Datum
                    $noDate = $activeOccurrences->filter(fn($o) => !$o['planned_at']);
                    if ($noDate->count() > 0) {
                        $groups[] = ['title' => __('Ohne Datum'), 'tasks' => $noDate, 'color' => 'gray'];
                    }
                @endphp

                <div class="space-y-10">
                    @foreach($groups as $group)
                        @if($group['tasks']->count() > 0)
                            <div class="space-y-4">
                                <h2 class="text-sm font-black uppercase tracking-widest text-{{ $group['color'] }}-600 dark:text-{{ $group['color'] }}-400 flex items-center px-2">
                                    <span class="w-1.5 h-1.5 rounded-full bg-{{ $group['color'] }}-500 mr-2"></span>
                                    {{ $group['title'] }}
                                    <span class="ml-2 px-2 py-0.5 text-xs bg-{{ $group['color'] }}-100 dark:bg-{{ $group['color'] }}-900/30 rounded-full font-bold">
                                        {{ $group['tasks']->count() }}
                                    </span>
                                </h2>

                                <div class="grid grid-cols-1 gap-3">
                                    @foreach($group['tasks'] as $occurrence)
                                        @php
                                            $task = $occurrence['task'];
                                            $plannedAt = $occurrence['planned_at'];
                                        @endphp
                                        <div class="group bg-white dark:bg-gray-800 shadow-sm hover:shadow-md rounded-2xl p-4 flex items-center space-x-4 border border-gray-100 dark:border-gray-700 hover:border-{{ $group['color'] }}-300 dark:hover:border-{{ $group['color'] }}-700 transition-all">
                                            <div class="flex-shrink-0">
                                                <button wire:click="completeTask({{ $task->id }}, '{{ $plannedAt }}')" class="h-8 w-8 rounded-full border-2 border-gray-200 dark:border-gray-700 hover:border-green-500 dark:hover:border-green-400 flex items-center justify-center transition-all bg-gray-50 dark:bg-gray-900 group-hover:scale-110">
                                                    <svg class="h-5 w-5 text-transparent hover:text-green-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                            </div>

                                            <div class="flex-grow min-w-0">
                                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                                    <div class="flex flex-col">
                                                        <h3 class="text-base font-bold text-gray-900 dark:text-white truncate">
                                                            {{ $task->title }}
                                                        </h3>
                                                        @if($task->description)
                                                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $task->description }}</p>
                                                        @endif
                                                    </div>

                                                    <div class="flex items-center space-x-3 flex-shrink-0">
                                                        @if($plannedAt)
                                                            <div class="text-right">
                                                                <div class="text-lg font-black text-blue-600 dark:text-blue-400 underline decoration-2">
                                                                    {{ $plannedAt->format('H:i') }} <span class="text-xs">{{ __('Uhr') }}</span>
                                                                </div>
                                                                @if(!$plannedAt->isToday() && !$plannedAt->isTomorrow())
                                                                    <div class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">
                                                                        {{ $plannedAt->format('d.m.Y') }}
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endif

                                                        <div class="flex items-center -space-x-1">
                                                            @if($task->isRecurring())
                                                                <span title="{{ __('Wiederkehrend') }}" class="bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 p-1.5 rounded-lg border border-purple-200 dark:border-purple-800">
                                                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                                                </span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex items-center space-x-1 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button wire:click="editTask({{ $task->id }})" class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-all">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" /></svg>
                                                </button>
                                                <button wire:click="deleteTask({{ $task->id }}, '{{ $plannedAt }}')" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-all">
                                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach

                    @if($activeOccurrences->count() === 0)
                        <div class="text-center py-16 bg-white dark:bg-gray-800 rounded-3xl border-2 border-dashed border-gray-100 dark:border-gray-700 shadow-sm">
                            <div class="bg-blue-50 dark:bg-blue-900/20 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">{{ __('Alles erledigt!') }}</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('Zeit zum Entspannen oder eine neue Aufgabe erstellen.') }}</p>
                        </div>
                    @endif
                </div>
            </section>

            <!-- Completed Tasks -->
            @if($completedCompletions->count() > 0)
                <section>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white flex items-center">
                            <span class="w-2 h-6 bg-green-500 rounded-full mr-3"></span>
                            {{ __('Zuletzt erledigt') }}
                        </h2>
                    </div>

                    <div class="space-y-3">
                        @foreach($completedCompletions as $completion)
                            <div class="bg-white/50 dark:bg-gray-800/50 rounded-xl p-4 flex items-center justify-between border border-gray-100 dark:border-gray-700 opacity-75">
                                <div class="flex items-center space-x-3">
                                    <div class="h-6 w-6 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                                        <svg class="h-4 w-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </div>
                                    <div>
                                        <h3 class="text-base font-medium text-gray-600 dark:text-gray-400 line-through truncate">{{ $completion->task->title }}</h3>
                                        <p class="text-xs text-gray-400">
                                            {{ __('Erledigt :time', ['time' => $completion->completed_at->diffForHumans()]) }}
                                            @if($completion->planned_at)
                                                {{ __('(geplant für :time Uhr)', ['time' => $completion->planned_at->format('H:i')]) }}
                                            @endif
                                        </p>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </div>

    <!-- Deletion Confirmation Modal -->
    @if($confirmingTaskDeletion)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="cancelDeletion"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-700">
                    <div class="p-6 sm:p-8">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-xl bg-red-100 dark:bg-red-900/30 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                </svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white" id="modal-title">
                                    {{ __('Aufgabe löschen') }}
                                </h3>
                                <div class="mt-3">
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ __('Soll nur dieser eine Termin oder die gesamte Serie gelöscht werden?') }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-8 flex flex-col space-y-3">
                            <button type="button" wire:click="deleteOccurrence" class="w-full inline-flex justify-center items-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-bold rounded-xl shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all">
                                {{ __('Nur diesen Termin') }}
                            </button>
                            <button type="button" wire:click="deleteAll" class="w-full inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-bold rounded-xl shadow-lg text-white bg-red-600 hover:bg-red-700 transition-all">
                                {{ __('Gesamte Serie') }}
                            </button>
                            <button type="button" wire:click="cancelDeletion" class="w-full inline-flex justify-center items-center px-6 py-3 text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 transition-colors">
                                {{ __('Abbrechen') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @script
    <script>
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        if (timezone) {
            $wire.updateTimezone(timezone);
        }
    </script>
    @endscript
</div>
