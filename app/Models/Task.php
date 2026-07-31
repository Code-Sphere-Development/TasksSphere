<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'task_list_id',
        'title',
        'description',
        'due_at',
        'completed_at',
        'is_active',
        'is_archived',
        'recurrence_rule',
        'recurrence_timezone',
        'last_notified_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_notified_at' => 'datetime',
        'is_active' => 'boolean',
        'is_archived' => 'boolean',
        'recurrence_rule' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function completions()
    {
        return $this->hasMany(TaskCompletion::class);
    }

    public function taskList()
    {
        return $this->belongsTo(TaskList::class);
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    /**
     * Accessor for due_at to return in local timezone.
     */
    protected function dueAt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (! $value) {
                    return null;
                }
                $date = Carbon::parse($value);
                if ($this->recurrence_timezone) {
                    $date->setTimezone($this->recurrence_timezone);
                }

                return $date;
            }
        )->withoutObjectCaching();
    }

    /**
     * Accessor for completed_at to return in local timezone.
     */
    protected function completedAt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (! $value) {
                    return null;
                }
                $date = Carbon::parse($value);
                if ($this->recurrence_timezone) {
                    $date->setTimezone($this->recurrence_timezone);
                }

                return $date;
            }
        )->withoutObjectCaching();
    }

    /**
     * Accessor for last_notified_at to return in local timezone.
     */
    protected function lastNotifiedAt(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (! $value) {
                    return null;
                }
                $date = Carbon::parse($value);
                if ($this->recurrence_timezone) {
                    $date->setTimezone($this->recurrence_timezone);
                }

                return $date;
            }
        )->withoutObjectCaching();
    }

    public function isRecurring(): bool
    {
        return ! empty($this->recurrence_rule);
    }

    public function isHandledAt($date): bool
    {
        $timezone = $this->recurrence_timezone ?: config('app.timezone', 'UTC');
        $plannedAt = Carbon::parse($date, $timezone)->setTimezone('UTC');

        return $this->completions->contains(function ($completion) use ($plannedAt) {
            return $completion->planned_at->equalTo($plannedAt);
        });
    }

    public function isSkippedAt($date): bool
    {
        $timezone = $this->recurrence_timezone ?: config('app.timezone', 'UTC');
        $plannedAt = Carbon::parse($date, $timezone)->setTimezone('UTC');

        return $this->completions->contains(function ($completion) use ($plannedAt) {
            return $completion->planned_at->equalTo($plannedAt) && $completion->is_skipped;
        });
    }

    public function complete($plannedAt = null)
    {
        $timezone = $this->recurrence_timezone ?: config('app.timezone', 'UTC');
        $plannedAt = $plannedAt ? Carbon::parse($plannedAt, $timezone)->setTimezone('UTC') : ($this->due_at ?: now());

        if ($this->isRecurring()) {
            $this->completions()->updateOrCreate(
                ['planned_at' => $plannedAt],
                ['completed_at' => now(), 'is_skipped' => false]
            );

            // Update due_at to the next uncompleted occurrence
            $this->due_at = $this->calculateNextDueDate($plannedAt);
            $this->save();
        } else {
            $this->completed_at = now();
            $this->save();
        }
    }

    public function skip($plannedAt)
    {
        $timezone = $this->recurrence_timezone ?: config('app.timezone', 'UTC');
        $plannedAt = Carbon::parse($plannedAt, $timezone)->setTimezone('UTC');

        if ($this->isRecurring()) {
            $this->completions()->updateOrCreate(
                ['planned_at' => $plannedAt],
                ['is_skipped' => true, 'completed_at' => null]
            );

            // If we skipped the current due_at, we should move to the next one
            if ($this->due_at && $this->due_at->equalTo($plannedAt)) {
                $this->due_at = $this->calculateNextDueDate($plannedAt);
                $this->save();
            }
        }
    }

    public function getOccurrences($start, $end)
    {
        $timezone = $this->recurrence_timezone ?: config('app.timezone', 'UTC');
        $occurrences = collect();
        $start = Carbon::parse($start, $timezone)->setTimezone('UTC');
        $end = Carbon::parse($end, $timezone)->setTimezone('UTC');

        if (! $this->isRecurring()) {
            if (! $this->due_at) {
                // Task without date
                $occurrences->push([
                    'task' => $this,
                    'planned_at' => null,
                    'is_completed' => false,
                ]);
            } elseif ($this->due_at->isBefore($start)) {
                if (! $this->completed_at) {
                    // Overdue non-recurring
                    $occurrences->push([
                        'task' => $this,
                        'planned_at' => $this->due_at,
                        'is_completed' => false,
                    ]);
                }
            } elseif ($this->due_at->between($start, $end) || $this->due_at->isAfter($end)) {
                // Planned for future (within or after window)
                $occurrences->push([
                    'task' => $this,
                    'planned_at' => $this->due_at,
                    'is_completed' => $this->completed_at !== null,
                ]);
            }

            return $occurrences;
        }

        // Recurring logic
        $current = $this->due_at ? clone $this->due_at : $this->calculateNextDueDate(now()->subMinutes(1));

        if (! $current) {
            return $occurrences;
        }

        $tempDue = clone $current;
        $limit = 100;

        // If the current due_at is before our window, it's overdue
        if ($tempDue->isBefore($start)) {
            // Es ist überfällig. Wir zeigen es an.
            $occurrences->push([
                'task' => $this,
                'planned_at' => clone $tempDue,
                'is_completed' => false, // Da es due_at ist, ist es per Def. nicht erledigt
            ]);
            // Dann gehen wir zum nächsten
            $tempDue = $this->calculateNextDueDate($tempDue);
        }

        while ($tempDue && $tempDue->isBefore($end) && $limit > 0) {
            $occurrences->push([
                'task' => $this,
                'planned_at' => clone $tempDue,
                'is_completed' => $this->isHandledAt($tempDue),
            ]);

            $tempDue = $this->calculateNextDueDate($tempDue);
            $limit--;
        }

        return $occurrences;
    }

    public function calculateNextDueDate($from = null)
    {
        if (! $this->isRecurring()) {
            return null;
        }

        $rule = $this->recurrence_rule;
        $frequency = $rule['frequency'] ?? 'daily';
        $interval = $rule['interval'] ?? 1;
        $times = $rule['times'] ?? [];
        $weekdays = $rule['weekdays'] ?? []; // Array of integers 1 (Mon) to 7 (Sun)

        $timezone = $this->recurrence_timezone ?: config('app.timezone', 'UTC');

        // Wir arbeiten in der lokalen Zeitzone für die Berechnung
        $currentDue = $from ? Carbon::parse($from) : ($this->due_at ? clone $this->due_at : now());
        $currentDue->setTimezone($timezone);

        if (! empty($times)) {
            sort($times);
            $currentTimeStr = $currentDue->format('H:i');

            // Find the next time on the same day
            $nextTime = null;
            foreach ($times as $time) {
                if ($time > $currentTimeStr) {
                    $nextTime = $time;
                    break;
                }
            }

            if ($nextTime) {
                // There is a later time today
                [$hour, $minute] = explode(':', $nextTime);
                $result = $currentDue->copy()->setTime((int) $hour, (int) $minute);

                return $result->setTimezone('UTC');
            } else {
                // No more times today, move to next interval day
                if ($frequency === 'weekly' && ! empty($weekdays)) {
                    $nextDue = $currentDue->copy()->addDay();
                    $limit = 7;
                    while (! in_array($nextDue->dayOfWeekIso, $weekdays) && $limit > 0) {
                        $nextDue->addDay();
                        $limit--;
                    }
                } else {
                    $nextDue = $this->addInterval($currentDue, $frequency, $interval);
                }

                [$hour, $minute] = explode(':', $times[0]);
                $result = $nextDue->copy()->setTime((int) $hour, (int) $minute);

                return $result->setTimezone('UTC');
            }
        }

        // No specific times, just add interval
        if ($frequency === 'weekly' && ! empty($weekdays)) {
            $nextDue = $currentDue->copy()->addDay();
            $limit = 7;
            while (! in_array($nextDue->dayOfWeekIso, $weekdays) && $limit > 0) {
                $nextDue->addDay();
                $limit--;
            }

            return $nextDue->setTimezone('UTC');
        }

        return $this->addInterval($currentDue, $frequency, $interval)->setTimezone('UTC');
    }

    protected function addInterval($date, $frequency, $interval)
    {
        $newDate = clone $date;
        switch ($frequency) {
            case 'hourly':
                $newDate->addHours($interval);
                break;
            case 'daily':
                $newDate->addDays($interval);
                break;
            case 'weekly':
                $newDate->addWeeks($interval);
                break;
            case 'monthly':
                $newDate->addMonths($interval);
                break;
            default:
                $newDate->addDays($interval);
        }

        return $newDate;
    }
}
