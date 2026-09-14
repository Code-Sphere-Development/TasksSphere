<?php

namespace App\Support\Rotation;

use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Bestimmt, wer nach einer Erledigung als Naechstes zustaendig ist.
 *
 * Je Strategie eine Klasse, damit sie einzeln testbar bleiben und das ohnehin
 * grosse Task-Modell nicht weiter waechst.
 */
interface RotationStrategy
{
    /**
     * @param  Collection<int, User>  $candidates
     */
    public function next(Task $task, $candidates): ?User;
}
