<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Bis zu dieser Aenderung hat Task::complete() nur fuer wiederkehrende Aufgaben
 * einen Eintrag in task_completions geschrieben. Einmalige Aufgaben bekamen
 * lediglich ein completed_at und tauchten deshalb nie unter "Zuletzt erledigt"
 * auf. Diese Migration holt die fehlende Historie nach.
 *
 * Rein additiv: es werden ausschliesslich fehlende Zeilen eingefuegt,
 * bestehende bleiben unveraendert. Mehrfaches Ausfuehren ist gefahrlos.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('tasks')
            ->whereNotNull('completed_at')
            ->whereNull('recurrence_rule')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->chunkById(500, function ($tasks) {
                $rows = [];

                foreach ($tasks as $task) {
                    $plannedAt = $task->due_at ?: $task->completed_at;

                    $exists = DB::table('task_completions')
                        ->where('task_id', $task->id)
                        ->where('planned_at', $plannedAt)
                        ->exists();

                    if ($exists) {
                        continue;
                    }

                    $rows[] = [
                        'task_id' => $task->id,
                        'planned_at' => $plannedAt,
                        'completed_at' => $task->completed_at,
                        'is_skipped' => false,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($rows) {
                    DB::table('task_completions')->insert($rows);
                }
            });
    }

    /**
     * Bewusst ohne Gegenstueck: Nachtraeglich laesst sich nicht mehr
     * unterscheiden, welche Zeilen aus diesem Backfill stammen und welche
     * regulaer erledigt wurden. Ein Loeschen wuerde echte Historie vernichten.
     */
    public function down(): void
    {
        //
    }
};
