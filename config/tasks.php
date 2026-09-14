<?php

use App\Support\People\LocalPeopleDirectory;

return [

    /*
    |--------------------------------------------------------------------------
    | Stille Geräte
    |--------------------------------------------------------------------------
    |
    | Ein Gerät, das sich seit dieser Anzahl Tage nicht mehr beim Server
    | gemeldet hat, bekommt keine Push-Benachrichtigungen mehr. Nötig, weil ein
    | FCM-Token gültig bleibt, auch wenn die App ihren Auth-Token lokal verloren
    | hat - der Server erführe davon sonst nie.
    |
    */

    'device_stale_after_days' => (int) env('TASKS_DEVICE_STALE_AFTER_DAYS', 180),

    /*
    | Wie lange nach dem letzten Schreibzugriff der Zeitstempel unangetastet
    | bleibt. Ohne diese Drossel löste jeder API-Aufruf der App einen
    | Schreibzugriff auf user_devices aus.
    */

    'device_seen_throttle_minutes' => (int) env('TASKS_DEVICE_SEEN_THROTTLE_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Herkunft aus dem Token-Namen
    |--------------------------------------------------------------------------
    |
    | Schickt ein Client kein source-Feld mit, wird die Herkunft über den Namen
    | seines Sanctum-Tokens bestimmt. Nicht aufgeführte Namen gelten als
    | manuell - das hält bestehende Clients wie die Mobil-App unverändert.
    |
    | Erlaubte Werte: siehe App\Enums\TaskSource.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Personenverzeichnis
    |--------------------------------------------------------------------------
    |
    | Wer beantwortet die Frage, welchen Menschen Aufgaben zugewiesen werden
    | koennen. Standard ist die eigene Datenbank; sobald FamilyNetwork
    | angebunden ist, tritt hier eine andere Implementierung an die Stelle.
    |
    */

    'people_directory' => env('TASKS_PEOPLE_DIRECTORY', LocalPeopleDirectory::class),

    'sources_by_token_name' => [
        'gehirn-agent' => 'agent',
    ],

];
