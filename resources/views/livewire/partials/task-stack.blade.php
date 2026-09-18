{{-- Kompakter Stapel in der Randspalte. Erwartet $title und $occurrences. --}}
@if($occurrences->count() > 0)
    <section>
        <h2 class="flex items-baseline justify-between text-sm font-bold text-gray-900 dark:text-white">
            {{ $title }}
            <span class="text-xs font-medium text-gray-400">{{ $occurrences->count() }}</span>
        </h2>

        <ul class="mt-2 divide-y divide-gray-100 dark:divide-gray-700">
            @foreach($occurrences as $occurrence)
                @php
                    $task = $occurrence['task'];
                    $plannedAt = $occurrence['planned_at'];
                @endphp
                <li class="flex items-center gap-1">
                    <button type="button" wire:click="showTaskDetail({{ $task->id }}, '{{ $plannedAt }}')"
                            class="flex-grow min-w-0 flex items-center gap-2 py-2 text-left rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full {{ $task->priority?->accentClass() ?? 'bg-gray-200 dark:bg-gray-600' }}" aria-hidden="true"></span>
                        @if($plannedAt)
                            <span class="w-10 flex-shrink-0 tabular-nums text-xs text-gray-500 dark:text-gray-400">{{ $plannedAt->format('H:i') }}</span>
                        @endif
                        <span class="flex-grow min-w-0 truncate text-sm text-gray-700 dark:text-gray-300" title="{{ $task->title }}">{{ $task->displayTitle }}</span>
                        @if($task->assignedTo)
                            <span class="flex-shrink-0 inline-flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 dark:bg-teal-900/40 text-[10px] font-bold text-teal-700 dark:text-teal-300"
                                  title="{{ $task->assignedTo->name }}">
                                {{ \Illuminate\Support\Str::of($task->assignedTo->name)->substr(0, 1)->upper() }}
                            </span>
                        @endif
                    </button>

                    {{-- Auch hier abhakbar: ohne diesen Knopf kostet das Erledigen
                         drei Schritte ueber die Detailansicht. --}}
                    <button type="button" wire:click="completeTask({{ $task->id }}, '{{ $plannedAt }}')"
                            title="{{ __('Erledigen') }}" aria-label="{{ __('Erledigen') }}"
                            class="flex-shrink-0 h-8 w-8 rounded-full border-2 border-gray-200 dark:border-gray-600 text-gray-300 dark:text-gray-600 hover:border-emerald-500 hover:bg-emerald-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 flex items-center justify-center transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </button>
                </li>
            @endforeach
        </ul>
    </section>
@endif
