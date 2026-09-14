<?php

namespace App\Support\Rotation;

use App\Models\Task;
use App\Models\TaskCompletion;
use App\Models\User;

/**
 * Wer bisher am wenigsten erledigt hat, ist als Naechstes dran.
 *
 * Bei Gleichstand entscheidet die kleinste Id - das haelt die Auswahl
 * nachvollziehbar und die Tests stabil.
 */
class LeastCompletedRotation implements RotationStrategy
{
    public function next(Task $task, $candidates): ?User
    {
        $counts = TaskCompletion::query()
            ->whereIn('completed_by', $candidates->pluck('id'))
            ->where('is_skipped', false)
            ->selectRaw('completed_by, count(*) as total')
            ->groupBy('completed_by')
            ->pluck('total', 'completed_by');

        return $candidates
            ->sortBy(fn (User $user) => [$counts[$user->id] ?? 0, $user->id])
            ->first();
    }
}
