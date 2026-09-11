<?php

namespace App\Support;

use App\Enums\TaskSource;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Bestimmt die Herkunft einer neu angelegten Aufgabe.
 *
 * Vorrang hat immer die ausdrückliche Angabe des Clients. Fehlt sie, wird der
 * Name des Sanctum-Tokens gegen die Zuordnung in config/tasks.php geprüft.
 * Session-Anmeldungen (Weboberfläche) haben keinen benannten Token und gelten
 * damit als manuell.
 */
class TaskSourceResolver
{
    public function resolve(?string $explicit, mixed $accessToken = null): TaskSource
    {
        if ($explicit !== null) {
            return TaskSource::from($explicit);
        }

        return $this->fromTokenName($accessToken);
    }

    private function fromTokenName(mixed $accessToken): TaskSource
    {
        if (! $accessToken instanceof PersonalAccessToken) {
            return TaskSource::Manual;
        }

        $mapping = (array) config('tasks.sources_by_token_name', []);
        $source = $mapping[$accessToken->name] ?? null;

        return $source ? TaskSource::from($source) : TaskSource::Manual;
    }
}
