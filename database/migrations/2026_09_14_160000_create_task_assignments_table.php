<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Zuweisung von Aufgaben an Personen. Die Spaltennamen folgen dem Datenmodell
 * aus tasksphere_prd.md.
 *
 * task_assignments ist der Kreis moeglicher Zustaendiger je Aufgabe,
 * tasks.assigned_to die aktuell zustaendige Person. Die Trennung braucht es
 * fuer die Rotation: ohne Kreis gaebe es niemanden, aus dem gewaehlt wird.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['task_id', 'user_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            // nullOnDelete: verlaesst jemand den Haushalt, bleibt die Aufgabe
            // bestehen und faellt an niemanden zurueck, statt geloescht zu werden.
            $table->foreignId('assigned_to')->nullable()->after('user_id')
                ->constrained('users')->nullOnDelete();
            $table->index(['assigned_to', 'due_at']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['assigned_to', 'due_at']);
            $table->dropConstrainedForeignId('assigned_to');
        });

        Schema::dropIfExists('task_assignments');
    }
};
