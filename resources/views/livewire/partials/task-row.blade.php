{{-- Eine Aufgabenzeile im Hauptbereich. Erwartet $occurrence. --}}
@php
    $task = $occurrence['task'];
    $plannedAt = $occurrence['planned_at'];
    $isOverdue = $plannedAt && $plannedAt->isPast() && ! $plannedAt->isToday();
@endphp

<div class="group relative flex items-center gap-3 sm:gap-4 rounded-xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 py-3 pl-4 pr-3 transition-shadow hover:shadow-sm">
    <span class="absolute left-0 inset-y-3 w-1 rounded-r-full {{ $task->priority?->accentClass() ?? 'bg-gray-200 dark:bg-gray-700' }}" aria-hidden="true"></span>

    <time class="w-12 sm:w-14 flex-shrink-0 tabular-nums text-sm font-bold text-gray-900 dark:text-gray-100"
          @if($plannedAt) datetime="{{ $plannedAt->toIso8601String() }}" @endif>
        {{ $plannedAt?->format('H:i') ?? '—' }}
    </time>

    <button type="button" wire:click="showTaskDetail({{ $task->id }}, '{{ $plannedAt }}')"
            class="flex-grow min-w-0 text-left rounded-lg focus:outline-none focus:ring-2 focus:ring-brand-500">
        {{-- Der Verweis liegt im Textfluss, nicht als eigene Spalte: sonst quetscht
             er auf schmalen Bildschirmen den Titel auf ein Zeichen zusammen. --}}
        <span class="text-[15px] font-semibold text-gray-900 dark:text-white line-clamp-3 sm:line-clamp-2 break-words">
            @if($task->titleReference)
                <span class="mr-1.5 inline-block rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 align-[2px] text-[11px] font-medium text-gray-500 dark:text-gray-400">{{ $task->titleReference }}</span>
            @endif
            {{ $task->displayTitle }}
        </span>

        @if($task->description)
            <span class="mt-1 text-[13px] leading-snug text-gray-500 dark:text-gray-400 line-clamp-2 break-words">{{ $task->description }}</span>
        @endif

        <span class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500 dark:text-gray-400">
            @if($isOverdue)
                <span class="font-semibold text-red-600 dark:text-red-400">{{ $plannedAt->diffForHumans() }}</span>
            @endif

            @if($task->priority === \App\Enums\TaskPriority::Urgent)
                <span class="font-semibold text-red-600 dark:text-red-400">{{ $task->priority->label() }}</span>
            @endif

            @if($task->assignedTo)
                <span class="inline-flex items-center gap-1.5">
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 dark:bg-teal-900/40 text-[10px] font-bold text-teal-700 dark:text-teal-300">
                        {{ \Illuminate\Support\Str::of($task->assignedTo->name)->substr(0, 1)->upper() }}
                    </span>
                    {{ $task->assignedTo->name }}
                </span>
            @endif

            @if($task->recurrenceShort())
                <span class="inline-flex items-center gap-1 truncate">
                    <svg class="h-3 w-3 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    {{ $task->recurrenceShort() }}
                </span>
            @endif

            @if($task->source && $task->source !== \App\Enums\TaskSource::Manual)
                <span class="inline-flex items-center rounded border border-gray-300 dark:border-gray-600 px-1.5 py-px text-[10px] font-medium">
                    {{ $task->source->label() }}
                </span>
            @endif
        </span>
    </button>

    <div class="hidden sm:flex items-center gap-1 flex-shrink-0 opacity-0 group-hover:opacity-100 group-focus-within:opacity-100 transition-opacity">
        <button type="button" wire:click="editTask({{ $task->id }})" title="{{ __('Bearbeiten') }}"
                class="p-2 text-gray-400 hover:text-brand-600 dark:hover:text-brand-400 rounded-lg hover:bg-brand-50 dark:hover:bg-brand-900/20 transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        </button>
        <button type="button" wire:click="deleteTask({{ $task->id }}, '{{ $plannedAt }}')" title="{{ __('Löschen') }}"
                class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    </div>

    <button type="button" wire:click="completeTask({{ $task->id }}, '{{ $plannedAt }}')"
            title="{{ __('Erledigen') }}" aria-label="{{ __('Erledigen') }}"
            class="flex-shrink-0 h-11 w-11 rounded-full border-2 border-gray-200 dark:border-gray-600 text-gray-300 dark:text-gray-600 hover:border-emerald-500 hover:bg-emerald-500 hover:text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 flex items-center justify-center transition-colors">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
    </button>
</div>
