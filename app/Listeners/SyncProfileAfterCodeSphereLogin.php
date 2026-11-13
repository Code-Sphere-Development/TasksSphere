<?php

namespace App\Listeners;

use App\Models\User;
use CodeSphere\OAuth\Events\CodeSphereLoggedIn;

/**
 * Haelt den lokalen Namens-Snapshot aktuell. Wird ueber die Event-Discovery
 * registriert.
 */
class SyncProfileAfterCodeSphereLogin
{
    public function handle(CodeSphereLoggedIn $event): void
    {
        /** @var User $user */
        $user = $event->user;

        $user->syncFromCodeSphere($event->userData());
    }
}
