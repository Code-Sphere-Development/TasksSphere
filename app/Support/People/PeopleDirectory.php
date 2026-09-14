<?php

namespace App\Support\People;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Die Naht zwischen TasksSphere und der Frage, welche Menschen es gibt.
 *
 * Heute beantwortet LocalPeopleDirectory sie aus der eigenen Datenbank. Sobald
 * FamilyNetwork angebunden ist, tritt eine zweite Implementierung an die
 * Stelle - Zuweisung, Rotation und Kalenderfilter merken davon nichts, weil
 * sie ausschliesslich dieses Interface kennen.
 */
interface PeopleDirectory
{
    /**
     * Personen, denen der Nutzer Aufgaben zuweisen darf. Enthaelt ihn selbst.
     *
     * @return Collection<int, User>
     */
    public function assignableFor(User $user): Collection;

    public function canAssign(User $actor, User $candidate): bool;
}
