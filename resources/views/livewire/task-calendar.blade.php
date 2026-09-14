<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <button type="button" wire:click="previousMonth" aria-label="{{ __('Vorheriger Monat') }}"
                        class="p-2 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white min-w-[12rem] text-center">{{ $monthLabel }}</h1>
                <button type="button" wire:click="nextMonth" aria-label="{{ __('Nächster Monat') }}"
                        class="p-2 rounded-xl border border-gray-200 dark:border-gray-700 text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
                <button type="button" wire:click="today" class="ml-1 text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                    {{ __('Heute') }}
                </button>
            </div>

            <div class="flex items-center gap-3">
                @if($people->count() > 1)
                    <select wire:model.live="personId" class="border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white rounded-xl shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm p-2.5">
                        <option value="">{{ __('Alle Personen') }}</option>
                        @foreach($people as $person)
                            <option value="{{ $person->id }}">{{ $person->name }}</option>
                        @endforeach
                    </select>
                @endif
                <a href="{{ route('dashboard') }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline">
                    {{ __('Zu den Aufgaben') }}
                </a>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-gray-100 dark:border-gray-700 shadow-sm overflow-x-auto">
            <div class="min-w-[44rem]">
                <div class="grid grid-cols-7 border-b border-gray-100 dark:border-gray-700">
                    @foreach([1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'] as $label)
                        <div class="px-2 py-3 text-center text-xs font-black uppercase tracking-widest text-gray-400">{{ __($label) }}</div>
                    @endforeach
                </div>

                @foreach($weeks as $week)
                    <div class="grid grid-cols-7 border-b border-gray-100 dark:border-gray-700 last:border-b-0">
                        @foreach($week as $day)
                            <div @class([
                                'min-h-[7rem] p-2 border-r border-gray-100 dark:border-gray-700 last:border-r-0 align-top',
                                'bg-gray-50 dark:bg-gray-900/40' => ! $day['inMonth'],
                            ])>
                                <div @class([
                                    'text-xs font-bold mb-1',
                                    'text-blue-600 dark:text-blue-400' => $day['date']->isToday(),
                                    'text-gray-400' => ! $day['date']->isToday() && ! $day['inMonth'],
                                    'text-gray-600 dark:text-gray-300' => ! $day['date']->isToday() && $day['inMonth'],
                                ])>
                                    {{ $day['date']->day }}
                                </div>

                                <div class="space-y-1">
                                    @foreach($day['occurrences'] as $occurrence)
                                        @php($task = $occurrence['task'])
                                        <div @class([
                                            'px-2 py-1 rounded-lg text-[11px] leading-tight border truncate',
                                            'line-through opacity-60' => $occurrence['is_completed'],
                                            $task->priority?->badgeClasses() ?: 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-700 dark:text-gray-300 dark:border-gray-600',
                                        ]) title="{{ $task->title }}">
                                            <span class="font-bold">{{ $occurrence['planned_at']->format('H:i') }}</span>
                                            {{ $task->displayTitle }}
                                            @if($task->assignedTo)
                                                <span class="block truncate opacity-75">{{ $task->assignedTo->name }}</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
