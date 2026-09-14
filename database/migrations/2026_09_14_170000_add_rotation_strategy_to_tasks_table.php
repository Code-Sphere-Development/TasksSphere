<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wie die naechste zustaendige Person bestimmt wird. Leer heisst: kein
 * Wechsel, wer zustaendig ist, bleibt es.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('rotation_strategy', 24)->nullable()->after('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('rotation_strategy');
        });
    }
};
