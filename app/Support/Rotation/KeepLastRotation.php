<?php

namespace App\Support\Rotation;

use App\Models\Task;
use App\Models\User;

/**
 * Wer zustaendig war, bleibt es. Fuer Aufgaben, die zwar einen Kreis haben,
 * aber faktisch immer dieselbe Person erledigt.
 */
class KeepLastRotation implements RotationStrategy
{
    public function next(Task $task, $candidates): ?User
    {
        return $candidates->firstWhere('id', $task->assigned_to) ?? $candidates->first();
    }
}
