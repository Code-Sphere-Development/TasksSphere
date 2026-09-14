<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Der Kreis der Menschen, denen Aufgaben zugewiesen werden koennen.
 *
 * Bewusst schlank: Sobald FamilyNetwork angebunden ist, verweist external_ref
 * auf die dortige Familie und die Mitglieder kommen von dort. Bis dahin
 * funktioniert TasksSphere eigenstaendig - genau das ist die Anforderung.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            // Spaeter: id der Familie in FamilyNetwork. Referenzieren statt kopieren,
            // wie FamilyNetwork es mit external_links ohnehin haelt.
            $table->string('external_ref')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('household_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 16)->default('member');
            $table->timestamps();
            $table->unique(['household_id', 'user_id']);
            $table->index(['user_id', 'household_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('household_user');
        Schema::dropIfExists('households');
    }
};
