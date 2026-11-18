<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ordnet ein Token dem Geraet zu, das sich damit angemeldet hat. Meldet sich
 * dasselbe Geraet erneut an, werden seine alten Tokens widerrufen - sonst
 * sammelt jede Neuanmeldung der App eine weitere Sitzung an.
 *
 * Bestehende Tokens bleiben ohne Zuordnung und damit unberuehrt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->string('device_id')->nullable()->after('name');
            $table->index(['tokenable_type', 'tokenable_id', 'device_id']);
        });
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex(['tokenable_type', 'tokenable_id', 'device_id']);
            $table->dropColumn('device_id');
        });
    }
};
