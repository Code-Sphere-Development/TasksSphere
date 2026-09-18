<div class="p-4 sm:p-6 lg:p-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
    <div class="mx-auto">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div class="flex gap-2 md:ml-auto">
                <a href="{{ route('lists.index') }}" wire:navigate class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-full text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 transition-all">
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    {{ __('Listen') }}
                </a>
                <button type="button" wire:click="showCreateForm" class="inline-flex items-center px-4 py-2 border border-transparent rounded-full shadow-sm text-sm font-medium text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all">
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
                    <span class="bg-brand-100 dark:bg-brand-900 text-brand-600 dark:text-brand-300 p-2 rounded-lg mr-3">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </span>
                    {{ $isEditing ? __('Aufgabe bearbeiten') : __('Was steht an?') }}
                </h2>

                <form wire:submit.prevent="{{ $isEditing ? 'updateTask' : 'createTask' }}" class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label for="title" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Titel der Aufgabe') }}</label>
                            <input type="text" id="title" wire:model="title" placeholder="{{ __('z.B. Wäsche waschen') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 transition-colors sm:text-sm p-3">
                            @error('title') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="description" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Details (optional)') }}</label>
                            <textarea id="description" wire:model="description" rows="2" placeholder="{{ __('Weitere Informationen...') }}" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 transition-colors sm:text-sm p-3"></textarea>
                        </div>

                        <div x-show="!['daily', 'weekly'].includes($wire.frequency)">
                            <label for="due_at" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Datum & Uhrzeit') }}</label>
                            <div class="mt-1 relative">
                                <input type="datetime-local" id="due_at" wire:model="due_at" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 transition-colors sm:text-sm p-3">
                            </div>
                            @error('due_at') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div x-show="['daily', 'weekly'].includes($wire.frequency)">
                            <label for="due_at_start" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Startet am (optional)') }}</label>
                            <div class="mt-1 relative">
                                <input type="date" id="due_at_start" wire:model="due_at" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 transition-colors sm:text-sm p-3">
                            </div>
                            @error('due_at') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            <p class="mt-1 text-xs text-gray-500">{{ __('Standardmäßig heute.') }}</p>
                        </div>

                        @if($assignablePeople->count() > 1)
                            <div class="md:col-span-2">
                                <span class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Kreis') }}</span>
                                <p class="text-xs text-gray-400">{{ __('Wer diese Aufgabe übernehmen kann.') }}</p>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    @foreach($assignablePeople as $person)
                                        <label class="inline-flex items-center px-3 py-2 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 cursor-pointer">
                                            <input type="checkbox" value="{{ $person->id }}" wire:model="assignees" class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500">
                                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $person->name }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            <div>
                                <label for="rotation_strategy" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Wechsel') }}</label>
                                <select id="rotation_strategy" wire:model="rotation_strategy" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 transition-colors sm:text-sm p-3">
                                    <option value="">{{ __('Kein Wechsel') }}</option>
                                    @foreach(\App\Enums\TaskRotation::cases() as $case)
                                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="assigned_to" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Zuständig') }}</label>
                                <select id="assigned_to" wire:model="assigned_to" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 transition-colors sm:text-sm p-3">
                                    <option value="">{{ __('Niemand') }}</option>
                                    @foreach($assignablePeople as $person)
                                        <option value="{{ $person->id }}">{{ $person->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div>
                            <label for="priority" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Priorität') }}</label>
                            <select id="priority" wire:model="priority" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 transition-colors sm:text-sm p-3">
                                <option value="">{{ __('Keine Priorität') }}</option>
                                @foreach(\App\Enums\TaskPriority::cases() as $case)
                                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <div>
                                <label for="frequency" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Wiederholung') }}</label>
                                <select id="frequency" wire:model.live="frequency" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 transition-colors sm:text-sm p-3">
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
                            <select id="recurrence_timezone" wire:model.live="recurrence_timezone" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 transition-colors sm:text-sm p-3">
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
                                        <input type="checkbox" wire:model="weekdays" value="{{ $value }}" class="h-4 w-4 text-brand-600 focus:ring-brand-500 border-gray-300 rounded">
                                        <span class="ml-3 text-sm font-medium text-gray-700 dark:text-gray-300">{{ __($label) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-show="$wire.frequency !== 'none'" x-transition>
                            <div class="md:col-span-2">
                                <label for="newTime" class="block text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Uhrzeit(en) für die Wiederholung hinzufügen') }}</label>
                                <div class="mt-1 flex space-x-2">
                                    <input type="time" id="newTime" wire:model="newTime" class="block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-brand-500 focus:border-brand-500 transition-colors sm:text-sm p-3">
                                    <button type="button" wire:click="addTime" class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-bold rounded-xl text-white bg-brand-600 hover:bg-brand-700 transition-all shadow-sm">
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
                                    <span class="inline-flex items-center px-3 py-1.5 rounded-xl text-sm font-bold bg-brand-100 text-brand-800 dark:bg-brand-900/50 dark:text-brand-200 border border-brand-200 dark:border-brand-800">
                                        {{ $time }} {{ __('Uhr') }}
                                        <button type="button" wire:click="removeTime({{ $index }})" class="ml-2 inline-flex items-center p-0.5 rounded-lg hover:bg-brand-200 dark:hover:bg-brand-800 transition-colors">
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
                        <button type="submit" class="inline-flex items-center justify-center px-8 py-3 border border-transparent text-base font-bold rounded-xl shadow-lg text-white bg-brand-600 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 transition-all transform hover:-translate-y-0.5">
                            {{ $isEditing ? __('Änderungen speichern') : __('Aufgabe speichern') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,1fr)_18rem] gap-6 lg:gap-8">

            {{-- Hauptspalte: ausschliesslich offene Arbeit, nach Dringlichkeit. --}}
            <div class="space-y-8">
                @if($groups['overdue']->count() > 0)
                    <section>
                        <h2 class="flex items-center gap-2 text-sm font-bold text-red-600 dark:text-red-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            {{ trans_choice('{1} :count überfällig|[2,*] :count überfällig', $groups['overdue']->count(), ['count' => $groups['overdue']->count()]) }}
                        </h2>
                        <div class="mt-3 space-y-2">
                            @foreach($groups['overdue'] as $occurrence)
                                @include('livewire.partials.task-row')
                            @endforeach
                        </div>
                    </section>
                @endif

                <section>
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-2">
                        <h1 class="text-2xl font-black text-gray-900 dark:text-white tracking-tight">
                            {{ now()->translatedFormat('l, j. F') }}
                        </h1>
                        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ __(':count offen', ['count' => $todayCount]) }}</span>
                            <span>{{ __(':count erledigt', ['count' => $todayDoneCount]) }}</span>
                            @php($total = $todayCount + $todayDoneCount)
                            <span class="h-1 w-24 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden" aria-hidden="true">
                                <span class="block h-full rounded-full bg-emerald-500" style="width: {{ $total > 0 ? round($todayDoneCount / $total * 100) : 0 }}%"></span>
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2">
                        @forelse($groups['today'] as $occurrence)
                            @include('livewire.partials.task-row')
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-200 dark:border-gray-700 px-4 py-8 text-center">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white">
                                    {{ $todayDoneCount > 0 ? __('Heute ist alles erledigt.') : __('Für heute steht nichts an.') }}
                                </p>
                                <button type="button" wire:click="showCreateForm" class="mt-2 text-sm font-medium text-brand-600 dark:text-brand-400 hover:underline">
                                    {{ __('Aufgabe hinzufügen') }}
                                </button>
                            </div>
                        @endforelse
                    </div>
                </section>

                {{-- Die kommenden Tage gehoeren in die breite Spalte: es ist offene
                     Arbeit und muss mit einem Klick abhakbar sein. --}}
                @foreach($groups['upcoming'] as $group)
                    <section>
                        <h2 class="flex items-baseline justify-between text-sm font-bold text-gray-900 dark:text-white">
                            {{ $group['title'] }}
                            <span class="text-xs font-medium text-gray-400">{{ $group['occurrences']->count() }}</span>
                        </h2>
                        <div class="mt-3 space-y-2">
                            @foreach($group['occurrences'] as $occurrence)
                                @include('livewire.partials.task-row')
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>

            {{-- Randspalte: nur Nachrangiges. --}}
            <aside class="space-y-6 lg:border-l lg:border-gray-100 lg:dark:border-gray-700 lg:pl-6">
                @include('livewire.partials.task-stack', ['title' => __('Später'), 'occurrences' => $groups['later']])
                @include('livewire.partials.task-stack', ['title' => __('Ohne Datum'), 'occurrences' => $groups['undated']])

                @if($completedCompletions->count() > 0)
                    <details class="group">
                        <summary class="flex cursor-pointer items-baseline justify-between text-sm font-bold text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                            {{ __('Zuletzt erledigt') }}
                            <span class="text-xs font-medium text-gray-400">{{ $completedCompletions->count() }}</span>
                        </summary>
                        <ul class="mt-2 divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach($completedCompletions as $completion)
                                <li class="py-2 flex items-start gap-2">
                                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-emerald-500" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm text-gray-500 dark:text-gray-400 line-through">{{ $completion->task->displayTitle }}</span>
                                        <span class="block text-xs text-gray-400">
                                            {{ $completion->completed_at->diffForHumans() }}@if($completion->completedBy), {{ $completion->completedBy->name }}@endif
                                        </span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </details>
                @endif
            </aside>
        </div>
    </div>

    <!-- Task Detail Modal -->
    @if($detailTask)
        @include('livewire.partials.task-detail-modal')
    @endif

    <!-- Deletion Confirmation Modal -->
    @if($confirmingTaskDeletion)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true" wire:click="cancelDeletion"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-700">
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
