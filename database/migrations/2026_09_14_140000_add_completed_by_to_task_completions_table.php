<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bisher hielt task_completions nur fest, DASS ein Termin erledigt wurde, nicht
 * von wem. Das ist Voraussetzung fuer die Historie in einem Haushalt und fuer
 * die Rotationsstrategie "am seltensten erledigt".
 *
 * nullOnDelete, damit das Loeschen eines Kontos die Historie nicht mitreisst -
 * der Eintrag bleibt, nur die Zuordnung entfaellt.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('task_completions', function (Blueprint $table) {
            $table->foreignId('completed_by')->nullable()->after('task_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('task_completions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('completed_by');
        });
    }
};
