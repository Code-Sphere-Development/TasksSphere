{{-- Detailansicht einer Aufgabe. Erwartet $detailTask und optional $detailPlannedAt. --}}
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="task-detail-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500/75 transition-opacity" aria-hidden="true" wire:click="closeTaskDetail"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-2xl text-left overflow-hidden shadow-xl transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-gray-100 dark:border-gray-700">
            <div class="p-6 sm:p-8">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white break-words" id="task-detail-title">
                            {{ $detailTask->title }}
                        </h3>
                        @if($detailTask->source)
                            <span class="mt-2 inline-block text-[10px] font-bold uppercase tracking-wide px-2 py-0.5 rounded-md bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800">
                                {{ $detailTask->source->label() }}
                            </span>
                        @endif
                    </div>
                    <button type="button" wire:click="closeTaskDetail" aria-label="{{ __('Schließen') }}" class="flex-shrink-0 p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                @if($detailTask->description)
                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line break-words">{{ $detailTask->description }}</p>
                @endif

                <dl class="mt-6 space-y-3 text-sm">
                    @if($detailPlannedAt)
                        <div class="flex justify-between gap-4">
                            <dt class="font-semibold text-gray-500 dark:text-gray-400 flex-shrink-0">{{ __('Dieser Termin') }}</dt>
                            <dd class="text-right text-gray-900 dark:text-white">{{ \Illuminate\Support\Carbon::parse($detailPlannedAt)->format('d.m.Y H:i') }} {{ __('Uhr') }}</dd>
                        </div>
                    @endif

                    @if($detailTask->due_at)
                        <div class="flex justify-between gap-4">
                            <dt class="font-semibold text-gray-500 dark:text-gray-400 flex-shrink-0">{{ __('Fällig am') }}</dt>
                            <dd class="text-right text-gray-900 dark:text-white">{{ $detailTask->due_at->format('d.m.Y H:i') }} {{ __('Uhr') }}</dd>
                        </div>
                    @endif

                    @if($detailTask->recurrenceSummary())
                        <div class="flex justify-between gap-4">
                            <dt class="font-semibold text-gray-500 dark:text-gray-400 flex-shrink-0">{{ __('Wiederholung') }}</dt>
                            <dd class="text-right text-gray-900 dark:text-white break-words">{{ $detailTask->recurrenceSummary() }}</dd>
                        </div>
                    @endif

                    @if($detailTask->taskList)
                        <div class="flex justify-between gap-4">
                            <dt class="font-semibold text-gray-500 dark:text-gray-400 flex-shrink-0">{{ __('Liste') }}</dt>
                            <dd class="text-right">
                                <a href="{{ route('lists.show', $detailTask->taskList) }}" class="text-blue-600 dark:text-blue-400 hover:underline break-words">
                                    {{ $detailTask->taskList->title }}
                                </a>
                            </dd>
                        </div>
                    @endif

                    <div class="flex justify-between gap-4">
                        <dt class="font-semibold text-gray-500 dark:text-gray-400 flex-shrink-0">{{ __('Erstellt am') }}</dt>
                        <dd class="text-right text-gray-900 dark:text-white">{{ $detailTask->created_at->format('d.m.Y') }}</dd>
                    </div>
                </dl>

                <div class="mt-8 flex flex-col sm:flex-row-reverse gap-3">
                    <button type="button" wire:click="completeTask({{ $detailTask->id }}, '{{ $detailPlannedAt }}')" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-transparent text-base font-bold rounded-xl shadow-lg text-white bg-green-600 hover:bg-green-700 transition-all">
                        {{ __('Erledigen') }}
                    </button>
                    <button type="button" wire:click="editTask({{ $detailTask->id }})" class="w-full sm:w-auto inline-flex justify-center items-center px-6 py-3 border border-gray-300 dark:border-gray-600 text-base font-bold rounded-xl shadow-sm text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-all">
                        {{ __('Bearbeiten') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
