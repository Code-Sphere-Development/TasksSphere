<?php

namespace App\Livewire;

use App\Enums\TaskRotation;
use App\Enums\TaskSource;
use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;
use App\Support\People\PeopleDirectory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Component;

class TaskManager extends Component
{
    public $title;

    public $description;

    public $priority = '';

    public $assigned_to = '';

    public array $assignees = [];

    public $rotation_strategy = '';

    public $due_at;

    public $frequency = 'none';

    public $interval = 1;

    public $times = [];

    public $weekdays = [];

    public $newTime = '';

    public $editingTask = null;

    public $isEditing = false;

    public $showForm = false;

    public $confirmingTaskDeletion = false;

    public $deletionTaskId = null;

    public $deletionPlannedAt = null;

    public $recurrence_timezone;

    public ?int $detailTaskId = null;

    public ?string $detailPlannedAt = null;

    /**
     * Als Methode, nicht als Eigenschaft: Die zulaessigen Zustaendigen haengen
     * am angemeldeten Nutzer und stehen erst zur Laufzeit fest.
     */
    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'priority' => 'nullable|integer|in:1,2,3,4',
            'assigned_to' => ['nullable', 'integer', Rule::in($this->assignablePeople()->pluck('id'))],
            'assignees' => 'array',
            'assignees.*' => ['integer', Rule::in($this->assignablePeople()->pluck('id'))],
            'rotation_strategy' => ['nullable', Rule::enum(TaskRotation::class)],
            'due_at' => 'nullable|date',
            'frequency' => 'required|in:none,hourly,daily,weekly,monthly',
            'interval' => 'required|integer|min:1',
            'weekdays' => 'nullable|array',
            'recurrence_timezone' => 'required|string|timezone',
        ];
    }

    /**
     * Der Kreis wird gesetzt, nicht ergaenzt - sonst liessen sich Personen nie
     * wieder herausnehmen. assignTo() fuegt die zustaendige Person danach
     * ohnehin wieder hinzu.
     */
    private function syncAssignees(Task $task): void
    {
        $task->assignees()->sync(
            collect($this->assignees)
                ->mapWithKeys(fn ($id) => [(int) $id => ['assigned_by' => Auth::id()]])
                ->all()
        );
    }

    private function assignablePeople(): Collection
    {
        return app(PeopleDirectory::class)->assignableFor(Auth::user());
    }

    public function mount(): void
    {
        $this->recurrence_timezone = Auth::user()->timezone ?: 'Europe/Berlin';
    }

    public function updateTimezone($timezone): void
    {
        if (Auth::user()->timezone !== $timezone) {
            Auth::user()->update(['timezone' => $timezone]);
            $this->recurrence_timezone = $timezone;
        }
    }

    public function updatedRecurrenceTimezone($value): void
    {
        Auth::user()->update(['timezone' => $value]);
    }

    public function addTime(): void
    {
        $this->validate([
            'newTime' => 'required|regex:/^[0-2][0-9]:[0-5][0-9]$/',
        ]);

        if (! in_array($this->newTime, $this->times)) {
            $this->times[] = $this->newTime;
            sort($this->times);
        }
        $this->newTime = '';
    }

    public function removeTime($index): void
    {
        unset($this->times[$index]);
        $this->times = array_values($this->times);
    }

    public function render(): Factory|View|\Illuminate\View\View
    {
        // Eigene Aufgaben und solche, fuer die ich zustaendig bin. Bewusst
        // nicht ueber Auth::user()->tasks(), das bildet nur den Besitz ab.
        $allTasks = Task::forPerson(Auth::id())
            ->with('completions', 'taskList', 'assignedTo')
            ->where('is_archived', false)
            ->where(function ($query) {
                $query->whereNull('completed_at')
                    ->orWhereNotNull('recurrence_rule');
            })
            ->get();

        $occurrences = collect();
        $start = now()->startOfDay();
        $end = now()->addDays(7)->endOfDay();

        foreach ($allTasks as $task) {
            $occurrences = $occurrences->merge($task->getOccurrences($start, $end));
        }

        // Sort by planned_at
        $occurrences = $occurrences->sortBy('planned_at');

        $completedCompletions = TaskCompletion::whereHas('task', function ($query) {
            $query->where('user_id', '=', Auth::id());
        })
            ->with('task')
            ->where('is_skipped', false)
            ->orderBy('completed_at', 'desc')
            ->take(10)
            ->get();

        // Count active occurrences for today
        $todayCount = $occurrences->filter(fn ($o) => ! $o['is_completed'] && $o['planned_at'] && $o['planned_at']->isToday())->count();

        return view('livewire.task-manager', [
            'groups' => $this->groupOccurrences($occurrences),
            'tasks' => $allTasks,
            'occurrences' => $occurrences,
            'todayCount' => $todayCount,
            'todayDoneCount' => $this->completedToday(),
            'completedCompletions' => $completedCompletions,
            'detailTask' => $this->detailTaskId ? $allTasks->firstWhere('id', $this->detailTaskId) : null,
            'assignablePeople' => $this->assignablePeople(),
        ]);
    }

    /**
     * Sortiert die Termine in die Bereiche des Dashboards ein.
     *
     * Lag bis hierher als @php-Block im Blade und damit ausserhalb jeder
     * Testabdeckung. Die Regeln sind unveraendert uebernommen: was heute
     * frueher faellig war, gilt als heute und nicht als ueberfaellig.
     *
     * @return array{overdue: Collection, today: Collection, upcoming: array<int, array{key: string, title: string, occurrences: Collection}>, later: Collection, undated: Collection}
     */
    /**
     * Wie viele Termine heute schon erledigt sind. Fuer den Tagesfortschritt.
     */
    protected function completedToday(): int
    {
        return TaskCompletion::whereHas('task', fn ($query) => $query->where('user_id', Auth::id())
            ->orWhere('assigned_to', Auth::id()))
            ->where('is_skipped', false)
            ->whereDate('completed_at', now()->toDateString())
            ->count();
    }

    protected function groupOccurrences(Collection $occurrences): array
    {
        $open = $occurrences->filter(fn ($o) => ! $o['is_completed']);
        $dated = $open->filter(fn ($o) => $o['planned_at'] !== null);
        $weekEnd = now()->addDays(7)->endOfDay();

        $upcoming = [];
        for ($offset = 1; $offset <= 7; $offset++) {
            $date = now()->addDays($offset);
            $ofDay = $dated->filter(fn ($o) => $o['planned_at']->isSameDay($date));

            if ($ofDay->isEmpty()) {
                continue;
            }

            $upcoming[] = [
                'key' => $date->toDateString(),
                'title' => $date->isTomorrow() ? __('Morgen') : $date->translatedFormat('l, d.m.'),
                'occurrences' => $ofDay->values(),
            ];
        }

        return [
            'overdue' => $dated->filter(fn ($o) => $o['planned_at']->isPast() && ! $o['planned_at']->isToday())->values(),
            'today' => $dated->filter(fn ($o) => $o['planned_at']->isToday())->values(),
            'upcoming' => $upcoming,
            'later' => $dated->filter(fn ($o) => $o['planned_at']->isAfter($weekEnd))->values(),
            'undated' => $open->filter(fn ($o) => $o['planned_at'] === null)->values(),
        ];
    }

    public function showTaskDetail(int $taskId, ?string $plannedAt = null): void
    {
        $task = Task::findOrFail($taskId);
        $this->authorize('view', $task);

        $this->detailTaskId = $task->id;
        $this->detailPlannedAt = $plannedAt;
    }

    public function closeTaskDetail(): void
    {
        $this->reset(['detailTaskId', 'detailPlannedAt']);
    }

    public function showCreateForm(): void
    {
        $this->cancelEdit();
        $this->showForm = true;
    }

    public function createTask(): void
    {
        $this->validate();

        $recurrence_rule = null;
        if ($this->frequency !== 'none') {
            $recurrence_rule = [
                'frequency' => $this->frequency,
                'interval' => (int) $this->interval,
                'times' => $this->times,
                'weekdays' => $this->frequency === 'weekly' ? $this->weekdays : [],
            ];
        }

        $dueAt = $this->prepareDueAt();

        $task = Auth::user()->tasks()->create([
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority !== '' ? (int) $this->priority : null,
            'due_at' => $dueAt,
            'recurrence_rule' => $recurrence_rule,
            'recurrence_timezone' => $this->recurrence_timezone,
            'rotation_strategy' => $this->rotation_strategy ?: null,
            'source' => TaskSource::Manual,
        ]);

        $this->syncAssignees($task);

        if ($this->assigned_to !== '') {
            $task->assignTo(User::findOrFail((int) $this->assigned_to), Auth::user());
        }

        $this->reset(['title', 'description', 'priority', 'assigned_to', 'assignees', 'rotation_strategy', 'due_at', 'frequency', 'interval', 'times', 'weekdays', 'newTime', 'showForm']);
    }

    public function editTask($taskId): void
    {
        $task = Auth::user()->tasks()->findOrFail($taskId);
        $this->editingTask = $task;
        $this->title = $task->title;
        $this->description = $task->description;
        $this->priority = $task->priority?->value ?? '';
        $this->assigned_to = $task->assigned_to ?? '';
        $this->assignees = $task->assignees->pluck('id')->all();
        $this->rotation_strategy = $task->rotation_strategy?->value ?? '';
        $this->due_at = $task->due_at ? $task->due_at->format('Y-m-d\TH:i') : null;

        if ($task->isRecurring()) {
            $this->frequency = $task->recurrence_rule['frequency'];
            $this->interval = $task->recurrence_rule['interval'];
            $this->times = $task->recurrence_rule['times'] ?? [];
            $this->weekdays = $task->recurrence_rule['weekdays'] ?? [];
        } else {
            $this->frequency = 'none';
            $this->interval = 1;
            $this->times = [];
            $this->weekdays = [];
        }
        $this->recurrence_timezone = $task->recurrence_timezone ?? 'Europe/Berlin';

        $this->isEditing = true;
        $this->closeTaskDetail();
    }

    public function updateTask(): void
    {
        $this->validate();

        $task = Auth::user()->tasks()->findOrFail($this->editingTask->id);

        $recurrence_rule = null;
        if ($this->frequency !== 'none') {
            $recurrence_rule = [
                'frequency' => $this->frequency,
                'interval' => (int) $this->interval,
                'times' => $this->times,
                'weekdays' => $this->frequency === 'weekly' ? $this->weekdays : [],
            ];
        }

        $dueAt = $this->prepareDueAt();

        $task->update([
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority !== '' ? (int) $this->priority : null,
            'due_at' => $dueAt,
            'recurrence_rule' => $recurrence_rule,
            'recurrence_timezone' => $this->recurrence_timezone,
            'rotation_strategy' => $this->rotation_strategy ?: null,
        ]);

        $this->syncAssignees($task);

        if ($this->assigned_to !== '') {
            $task->assignTo(User::findOrFail((int) $this->assigned_to), Auth::user());
        } else {
            $task->update(['assigned_to' => null]);
        }

        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        $this->reset(['title', 'description', 'priority', 'assigned_to', 'assignees', 'rotation_strategy', 'due_at', 'frequency', 'interval', 'times', 'weekdays', 'newTime', 'isEditing', 'editingTask', 'showForm']);
    }

    public function completeTask($taskId, $plannedAt = null): void
    {
        $task = Auth::user()->tasks()->findOrFail($taskId);
        $task->complete($plannedAt, Auth::user());
        $this->closeTaskDetail();
    }

    public function deleteTask($taskId, $plannedAt = null): void
    {
        $task = Auth::user()->tasks()->findOrFail($taskId);

        if ($task->isRecurring() && $plannedAt) {
            $this->deletionTaskId = $taskId;
            $this->deletionPlannedAt = $plannedAt;
            $this->confirmingTaskDeletion = true;
        } else {
            $task->delete();
        }
    }

    public function deleteOccurrence(): void
    {
        if ($this->deletionTaskId && $this->deletionPlannedAt) {
            $task = Auth::user()->tasks()->findOrFail($this->deletionTaskId);
            $task->skip($this->deletionPlannedAt);
            $this->cancelDeletion();
        }
    }

    public function deleteAll(): void
    {
        if ($this->deletionTaskId) {
            $task = Auth::user()->tasks()->findOrFail($this->deletionTaskId);
            $task->delete();
            $this->cancelDeletion();
        }
    }

    private function prepareDueAt(): ?string
    {
        $dueAt = $this->due_at;
        if (! $dueAt && in_array($this->frequency, ['hourly', 'daily', 'weekly', 'monthly'])) {
            $dueAt = now()->toDateTimeString();
        }

        if ($dueAt) {
            $date = Carbon::parse($dueAt, $this->recurrence_timezone);
            if ($this->frequency === 'weekly' && ! empty($this->weekdays)) {
                if (! in_array($date->dayOfWeekIso, $this->weekdays)) {
                    $limit = 7;
                    while (! in_array($date->dayOfWeekIso, $this->weekdays) && $limit > 0) {
                        $date->addDay();
                        $limit--;
                    }
                }
            }
            if ($this->frequency !== 'none' && ! empty($this->times)) {
                sort($this->times);
                [$hour, $minute] = explode(':', $this->times[0]);
                $date->setTime((int) $hour, (int) $minute);
            }
            $dueAt = $date->setTimezone('UTC')->toDateTimeString();
        }

        return $dueAt;
    }

    public function cancelDeletion(): void
    {
        $this->reset(['confirmingTaskDeletion', 'deletionTaskId', 'deletionPlannedAt']);
    }
}
