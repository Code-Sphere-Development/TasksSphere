<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ersetzt die gleichnamige Migration aus code-sphere/oauth - Laravel fuehrt
 * pro Dateiname nur eine Migration aus, die der App gewinnt.
 *
 * Die Paketversion legt codesphere_id als NOT NULL UNIQUE an. Bestehende
 * Konten bekaemen dabei alle denselben Wert und die Migration braeche ab.
 * Hier bleibt die Spalte leer, bis ein Konto per
 * `php artisan users:link-codesphere` gekoppelt ist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'codesphere_id')) {
                $table->unsignedBigInteger('codesphere_id')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('users', 'access_token')) {
                $table->text('access_token')->nullable();
            }
            if (! Schema::hasColumn('users', 'refresh_token')) {
                $table->text('refresh_token')->nullable();
            }
            if (! Schema::hasColumn('users', 'token_expires_at')) {
                $table->timestamp('token_expires_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_codesphere_id_unique');
            $table->dropColumn(['codesphere_id', 'access_token', 'refresh_token', 'token_expires_at']);
        });
    }
};
