<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Anmeldung laeuft ueber AccountSphere, lokale Zugangsdaten entfallen.
 *
 * Name, E-Mail und Sprache bleiben als Snapshot, den jeder Login aus
 * AccountSphere ueberschreibt: Haushalte, Zuweisungen, API und Konsolenbefehle
 * zeigen auch Nutzer an, deren Session gerade nicht existiert.
 *
 * E-Mail verliert die Eindeutigkeit, weil ein noch nicht gekoppeltes Altkonto
 * dieselbe Adresse tragen kann wie der Stub aus dem ersten SSO-Login.
 */
return new class extends Migration
{
    private const CREDENTIAL_COLUMNS = [
        'password',
        'remember_token',
        'email_verified_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'profile_photo_path',
        'current_team_id',
    ];

    public function up(): void
    {
        // SQLite kann keine Spalte loeschen, auf der noch ein Index liegt.
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_email_unique');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(array_values(array_filter(
                self::CREDENTIAL_COLUMNS,
                fn (string $column) => Schema::hasColumn('users', $column),
            )));
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable()->change();
            $table->string('last_name')->nullable()->change();
            $table->string('email')->nullable()->change();
            $table->index('email');
        });

        Schema::dropIfExists('password_reset_tokens');
    }

    /**
     * Stellt nur die Struktur wieder her. Passwoerter und 2FA-Geheimnisse sind
     * verloren; betroffene Konten muessen ihr Passwort zuruecksetzen.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['email']);
        });

        // Spalten bleiben nullable: SSO-Stubs haben unter Umstaenden keinen Namen.
        Schema::table('users', function (Blueprint $table) {
            $table->unique('email');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->rememberToken();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->foreignId('current_team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }
};
