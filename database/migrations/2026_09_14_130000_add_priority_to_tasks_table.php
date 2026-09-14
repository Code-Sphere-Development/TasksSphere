<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Aufgabenprioritaeten stehen in tasksphere_prd.md unter den Kernfunktionen,
 * fehlten aber bislang vollstaendig.
 *
 * Nullable, weil "keine Prioritaet" ein gueltiger und der haeufigste Zustand
 * ist - ein Standardwert wuerde jede Bestandsaufgabe faelschlich einstufen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedTinyInteger('priority')->nullable()->after('description');
            $table->index(['user_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'priority']);
            $table->dropColumn('priority');
        });
    }
};
