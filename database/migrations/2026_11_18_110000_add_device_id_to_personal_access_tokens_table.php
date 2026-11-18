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
 *
 * Der Index bekommt einen eigenen Namen: der automatisch erzeugte ist 66 Zeichen
 * lang, MySQL erlaubt hoechstens 64. Der erste Lauf in Produktion hat deshalb nur
 * die Spalte angelegt und ist dann abgebrochen - beide Schritte sind daher
 * wiederholbar.
 */
return new class extends Migration
{
    private const INDEX = 'pat_tokenable_device_index';

    public function up(): void
    {
        if (! Schema::hasColumn('personal_access_tokens', 'device_id')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->string('device_id')->nullable()->after('name');
            });
        }

        if (! Schema::hasIndex('personal_access_tokens', self::INDEX)) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->index(['tokenable_type', 'tokenable_id', 'device_id'], self::INDEX);
            });
        }
    }

    public function down(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->dropIndex(self::INDEX);
            $table->dropColumn('device_id');
        });
    }
};
