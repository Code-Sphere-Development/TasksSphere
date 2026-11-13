<?php

namespace App\Support\AccountSphere;

use RuntimeException;

class PasswordLoginFailed extends RuntimeException
{
    public function __construct(string $message, public readonly int $status)
    {
        parent::__construct($message);
    }

    public static function invalidCredentials(): self
    {
        return new self('Die Anmeldedaten sind falsch.', 422);
    }

    public static function deactivated(): self
    {
        return new self('Dieses Konto ist in CodeSphere deaktiviert.', 422);
    }

    public static function throttled(): self
    {
        return new self('Zu viele Anmeldeversuche. Bitte in einer Minute erneut versuchen.', 429);
    }

    public static function unavailable(): self
    {
        return new self('Die Anmeldung bei CodeSphere ist gerade nicht erreichbar.', 503);
    }
}
