<?php

namespace App\Support\People;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Beantwortet die Frage aus den Haushalten der eigenen Datenbank.
 */
class LocalPeopleDirectory implements PeopleDirectory
{
    public function assignableFor(User $user): Collection
    {
        $householdIds = $user->households()->pluck('households.id');

        if ($householdIds->isEmpty()) {
            return collect([$user]);
        }

        return User::whereHas('households', fn ($query) => $query->whereIn('households.id', $householdIds))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    public function canAssign(User $actor, User $candidate): bool
    {
        return $this->assignableFor($actor)->contains('id', $candidate->id);
    }
}
