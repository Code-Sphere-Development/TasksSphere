<?php

namespace App\Livewire;

use App\Models\Task;
use App\Support\People\PeopleDirectory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Monatsblick auf dieselben Termine, die das Dashboard erzeugt.
 *
 * Eigene Route und eigene Komponente, weil TaskManager mit ueber 400 Zeilen
 * schon das groesste Stueck der Oberflaeche ist.
 */
class TaskCalendar extends Component
{
    #[Url]
    public string $month = '';

    #[Url(except: null)]
    public ?int $personId = null;

    public function mount(): void
    {
        if ($this->month === '') {
            $this->month = now()->format('Y-m');
        }
    }

    public function render(): View
    {
        $start = $this->monthStart();
        $end = (clone $start)->endOfMonth();

        return view('livewire.task-calendar', [
            'weeks' => $this->weeks($start, $end),
            'monthLabel' => $start->translatedFormat('F Y'),
            'people' => app(PeopleDirectory::class)->assignableFor(Auth::user()),
        ])->layout('layouts.app');
    }

    public function previousMonth(): void
    {
        $this->month = $this->monthStart()->subMonth()->format('Y-m');
    }

    public function nextMonth(): void
    {
        $this->month = $this->monthStart()->addMonth()->format('Y-m');
    }

    public function today(): void
    {
        $this->month = now()->format('Y-m');
    }

    private function monthStart(): Carbon
    {
        return Carbon::createFromFormat('Y-m', $this->month)->startOfMonth();
    }

    /**
     * Das Gitter laeuft von Montag der ersten bis Sonntag der letzten Woche,
     * damit jede Zeile sieben Zellen hat.
     *
     * @return Collection<int, Collection<int, array{date: Carbon, inMonth: bool, occurrences: Collection}>>
     */
    private function weeks(Carbon $start, Carbon $end): Collection
    {
        $byDay = $this->occurrencesByDay($start, $end);

        $gridStart = (clone $start)->startOfWeek();
        $gridEnd = (clone $end)->endOfWeek();

        $days = collect();
        for ($date = clone $gridStart; $date->lessThanOrEqualTo($gridEnd); $date->addDay()) {
            $key = $date->toDateString();
            $days->push([
                'date' => clone $date,
                'inMonth' => $date->month === $start->month,
                'occurrences' => $byDay->get($key, collect()),
            ]);
        }

        return $days->chunk(7);
    }

    private function occurrencesByDay(Carbon $start, Carbon $end): Collection
    {
        $tasks = Task::forPerson(Auth::id())
            ->with('completions', 'assignedTo')
            ->where('is_archived', false)
            ->when($this->personId, fn ($query) => $query->where('assigned_to', $this->personId))
            ->where(function ($query) {
                $query->whereNull('completed_at')->orWhereNotNull('recurrence_rule');
            })
            ->get();

        return $tasks
            ->flatMap(fn (Task $task) => $task->getOccurrences($start, $end))
            ->filter(fn ($occurrence) => $occurrence['planned_at'] !== null)
            ->filter(fn ($occurrence) => $occurrence['planned_at']->betweenIncluded($start, $end))
            ->sortBy('planned_at')
            ->groupBy(fn ($occurrence) => $occurrence['planned_at']->toDateString());
    }
}
