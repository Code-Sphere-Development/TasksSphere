<?php

namespace App\Models;

use Database\Factories\HouseholdFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Ein Kreis von Menschen, die sich Aufgaben zuweisen koennen.
 *
 * Solange external_ref leer ist, verwaltet TasksSphere die Mitglieder selbst.
 * Ist es gesetzt, stammt die Familie aus FamilyNetwork und dieser Datensatz
 * ist nur noch der lokale Anker dafuer.
 */
class Household extends Model
{
    /** @use HasFactory<HouseholdFactory> */
    use HasFactory;

    protected $fillable = ['owner_id', 'name', 'external_ref'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    public static function createFor(User $owner, string $name): self
    {
        $household = static::create(['owner_id' => $owner->id, 'name' => $name]);
        $household->members()->attach($owner->id, ['role' => 'owner']);

        return $household->fresh();
    }

    public function addMember(User $user, string $role = 'member'): void
    {
        $this->members()->syncWithoutDetaching([$user->id => ['role' => $role]]);
    }

    /**
     * Der Besitzer bleibt immer Mitglied - sonst entstuende ein Haushalt, den
     * niemand mehr verwalten kann.
     */
    public function removeMember(User $user): void
    {
        if ($user->id === $this->owner_id) {
            return;
        }

        $this->members()->detach($user->id);
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->id)->exists();
    }
}
