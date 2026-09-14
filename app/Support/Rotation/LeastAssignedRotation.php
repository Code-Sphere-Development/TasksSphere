<?php

namespace App\Support\Rotation;

use App\Models\Task;
use App\Models\User;

/**
 * Wer aktuell fuer die wenigsten Aufgaben zustaendig ist, ist als Naechstes
 * dran. Bei Gleichstand entscheidet die kleinste Id.
 */
class LeastAssignedRotation implements RotationStrategy
{
    public function next(Task $task, $candidates): ?User
    {
        $counts = Task::query()
            ->whereIn('assigned_to', $candidates->pluck('id'))
            ->whereKeyNot($task->getKey())
            ->selectRaw('assigned_to, count(*) as total')
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to');

        return $candidates
            ->sortBy(fn (User $user) => [$counts[$user->id] ?? 0, $user->id])
            ->first();
    }
}
