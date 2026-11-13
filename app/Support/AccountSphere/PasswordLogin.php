<?php

namespace App\Support\AccountSphere;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Prueft E-Mail und Passwort gegen AccountSphere (POST /api/auth/login).
 *
 * Damit bleibt POST /api/login fuer Mobile-App, Home Assistant und den
 * Gehirn-Agenten unveraendert, obwohl TasksSphere keine Passwoerter mehr kennt.
 * TasksSphere braucht das dabei ausgestellte AccountSphere-Token nicht und
 * widerruft es sofort, damit sich dort keine Tokens ansammeln.
 */
class PasswordLogin
{
    /**
     * @return array<string, mixed> der user-Teil der AccountSphere-Antwort
     *
     * @throws PasswordLoginFailed
     */
    public function attempt(string $email, string $password): array
    {
        try {
            $response = $this->client()->post('/api/auth/login', [
                'email' => $email,
                'password' => $password,
            ]);
        } catch (ConnectionException $e) {
            Log::warning('AccountSphere: Login nicht erreichbar', ['error' => $e->getMessage()]);

            throw PasswordLoginFailed::unavailable();
        }

        $this->guard($response);

        $this->revoke((string) $response->json('access_token'));

        return (array) $response->json('user');
    }

    /**
     * @throws PasswordLoginFailed
     */
    private function guard(Response $response): void
    {
        $user = $response->json('user');

        if ($response->successful() && is_array($user) && isset($user['id'])) {
            return;
        }

        throw match ($response->status()) {
            401, 422 => PasswordLoginFailed::invalidCredentials(),
            403 => PasswordLoginFailed::deactivated(),
            429 => PasswordLoginFailed::throttled(),
            default => $this->unexpected($response),
        };
    }

    private function unexpected(Response $response): PasswordLoginFailed
    {
        Log::warning('AccountSphere: unerwartete Login-Antwort', ['status' => $response->status()]);

        return PasswordLoginFailed::unavailable();
    }

    private function revoke(string $accessToken): void
    {
        if ($accessToken === '') {
            return;
        }

        try {
            $this->client()->withToken($accessToken)->post('/api/auth/logout');
        } catch (ConnectionException $e) {
            // Der Login ist bereits gelungen; ein liegengebliebenes Token laeuft
            // in AccountSphere nach sechs Monaten von selbst ab.
            Log::info('AccountSphere: Token konnte nicht widerrufen werden', ['error' => $e->getMessage()]);
        }
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('codesphere.url'), '/'))
            ->acceptJson()
            ->timeout(10);
    }
}
