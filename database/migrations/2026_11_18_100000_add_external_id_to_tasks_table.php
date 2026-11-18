<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kennung, unter der ein Aufrufer (etwa der Gehirn-Agent) eine Aufgabe selbst
 * fuehrt. Wiederholt er POST /api/tasks nach einem Fehler, entsteht so keine
 * zweite Aufgabe. Eindeutig je Besitzer, nicht global: Zwei Nutzer duerfen
 * dieselbe Kennung verwenden.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('external_id')->nullable()->after('source');
            $table->unique(['user_id', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'external_id']);
            $table->dropColumn('external_id');
        });
    }
};
