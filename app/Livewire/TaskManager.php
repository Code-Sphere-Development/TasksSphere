<?php

namespace App\Livewire;

use App\Enums\TaskSource;
use App\Models\Task;
use App\Models\TaskCompletion;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class TaskManager extends Component
{
    public $title;

    public $description;

    public $priority = '';

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

    protected $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'priority' => 'nullable|integer|in:1,2,3,4',
        'due_at' => 'nullable|date',
        'frequency' => 'required|in:none,hourly,daily,weekly,monthly',
        'interval' => 'required|integer|min:1',
        'weekdays' => 'nullable|array',
        'recurrence_timezone' => 'required|string|timezone',
    ];

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
        $allTasks = Auth::user()->tasks()
            ->with('completions', 'taskList')
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
            'tasks' => $allTasks,
            'occurrences' => $occurrences,
            'todayCount' => $todayCount,
            'completedCompletions' => $completedCompletions,
            'detailTask' => $this->detailTaskId ? $allTasks->firstWhere('id', $this->detailTaskId) : null,
        ]);
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

        Auth::user()->tasks()->create([
            'title' => $this->title,
            'description' => $this->description,
            'priority' => $this->priority !== '' ? (int) $this->priority : null,
            'due_at' => $dueAt,
            'recurrence_rule' => $recurrence_rule,
            'recurrence_timezone' => $this->recurrence_timezone,
            'source' => TaskSource::Manual,
        ]);

        $this->reset(['title', 'description', 'priority', 'due_at', 'frequency', 'interval', 'times', 'weekdays', 'newTime', 'showForm']);
    }

    public function editTask($taskId): void
    {
        $task = Auth::user()->tasks()->findOrFail($taskId);
        $this->editingTask = $task;
        $this->title = $task->title;
        $this->description = $task->description;
        $this->priority = $task->priority?->value ?? '';
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
        ]);

        $this->cancelEdit();
    }

    public function cancelEdit(): void
    {
        $this->reset(['title', 'description', 'priority', 'due_at', 'frequency', 'interval', 'times', 'weekdays', 'newTime', 'isEditing', 'editingTask', 'showForm']);
    }

    public function completeTask($taskId, $plannedAt = null): void
    {
        $task = Auth::user()->tasks()->findOrFail($taskId);
        $task->complete($plannedAt);
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
