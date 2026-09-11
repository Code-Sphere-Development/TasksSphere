<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('source', 32)->default('manual');
        });

        // Der Agentenlauf im Homelab hat sich bisher mit einem Titelpräfix
        // beholfen. Die Herkunft wird daraus übernommen, der Titel bleibt
        // unangetastet - bewusste Entscheidung, das sind produktive Daten.
        DB::table('tasks')
            ->where('title', 'like', '[Agent] %')
            ->update(['source' => 'agent']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
