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
        Schema::table('user_devices', function (Blueprint $table) {
            $table->dateTime('last_seen_at')->nullable();
        });

        // Bestandsgeräte nicht schlagartig verstummen lassen: updated_at ist der
        // letzte Zeitpunkt, zu dem sich an der Registrierung etwas geändert hat,
        // und damit die beste verfügbare Näherung für "zuletzt gesehen".
        DB::table('user_devices')->update([
            'last_seen_at' => DB::raw('COALESCE(updated_at, created_at)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_devices', function (Blueprint $table) {
            $table->dropColumn('last_seen_at');
        });
    }
};
