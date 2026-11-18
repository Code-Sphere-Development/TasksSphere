<?php

namespace App\Observers;

use App\Models\Task;
use App\Notifications\TaskAssignedNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Beobachtet assigned_to statt jede Stelle einzeln, die zuweist: Weboberflaeche,
 * API und die Rotation nach einer Erledigung schreiben die Spalte alle direkt.
 */
class TaskObserver
{
    public function created(Task $task): void
    {
        $this->notifyAssignee($task);
    }

    public function updated(Task $task): void
    {
        if ($task->wasChanged('assigned_to')) {
            $this->notifyAssignee($task);
        }
    }

    private function notifyAssignee(Task $task): void
    {
        if (! $task->assigned_to) {
            return;
        }

        // Wer sich selbst zuweist oder bei der Rotation selbst wieder dran
        // ist, braucht keinen Hinweis.
        if ((int) $task->assigned_to === Auth::id()) {
            return;
        }

        // Frisch laden: Die Relation kann noch die vorherige Person zwischenspeichern.
        $assignee = $task->assignedTo()->first();

        if (! $assignee) {
            return;
        }

        // Die Zuweisung steht bereits; ein Fehler beim Einreihen darf das
        // Speichern nicht nachtraeglich scheitern lassen.
        try {
            $assignee->notify(new TaskAssignedNotification($task));
        } catch (\Throwable $e) {
            Log::error('Hinweis auf Zuweisung von Aufgabe '.$task->id.' konnte nicht eingereiht werden: '.$e->getMessage(), [
                'task_id' => $task->id,
                'user_id' => $assignee->id,
                'exception' => $e,
            ]);
        }
    }
}
