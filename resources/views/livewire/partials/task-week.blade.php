{{-- Die kommenden Tage als ein Stapel. Erwartet $days. --}}
@php
    $count = collect($days)->sum(fn ($day) => $day['occurrences']->count());
@endphp

@if($count > 0)
    <section>
        <h2 class="flex items-baseline justify-between text-sm font-bold text-gray-900 dark:text-white">
            {{ __('Diese Woche') }}
            <span class="text-xs font-medium text-gray-400">{{ $count }}</span>
        </h2>

        <div class="mt-2 space-y-3">
            @foreach($days as $day)
                <div>
                    <h3 class="text-xs font-semibold text-gray-400">{{ $day['title'] }}</h3>
                    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach($day['occurrences'] as $occurrence)
                            @php
                                $task = $occurrence['task'];
                                $plannedAt = $occurrence['planned_at'];
                            @endphp
                            <li>
                                <button type="button" wire:click="showTaskDetail({{ $task->id }}, '{{ $plannedAt }}')"
                                        class="w-full flex items-center gap-2 py-1.5 text-left rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <span class="h-1.5 w-1.5 flex-shrink-0 rounded-full {{ $task->priority?->accentClass() ?? 'bg-gray-200 dark:bg-gray-600' }}" aria-hidden="true"></span>
                                    <span class="w-10 flex-shrink-0 tabular-nums text-xs text-gray-400">{{ $plannedAt->format('H:i') }}</span>
                                    <span class="flex-grow min-w-0 truncate text-sm text-gray-700 dark:text-gray-300">{{ $task->title }}</span>
                                    @if($task->assignedTo)
                                        <span class="flex-shrink-0 inline-flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 dark:bg-teal-900/40 text-[10px] font-bold text-teal-700 dark:text-teal-300"
                                              title="{{ $task->assignedTo->name }}">
                                            {{ \Illuminate\Support\Str::of($task->assignedTo->name)->substr(0, 1)->upper() }}
                                        </span>
                                    @endif
                                </button>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </section>
@endif
