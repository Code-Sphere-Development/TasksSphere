<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Ersetzt die /login-Route aus code-sphere/oauth.
 *
 * Ohne Fehler geht es wie dort direkt zu AccountSphere. Scheitert der
 * Callback, leitet das Paket mit der Meldung hierher zurueck; die Route des
 * Pakets schickte dann sofort wieder zu AccountSphere, das einen bereits
 * angemeldeten Nutzer ohne Rueckfrage zurueckleitet - eine Endlosschleife.
 * Hier wird die Meldung stattdessen angezeigt.
 */
class LoginController extends Controller
{
    public function show(Request $request): RedirectResponse|View
    {
        $error = $request->session()->get('errors')?->first('oauth');

        if (! $error) {
            return redirect()->route('codesphere.redirect');
        }

        return view('auth.login', [
            'error' => $error,
            'switchAccountUrl' => $this->switchAccountUrl(),
        ]);
    }

    /**
     * Beendet die AccountSphere-Sitzung und kommt zur Anmeldung zurueck, damit
     * man sich mit einem anderen Konto anmelden kann.
     */
    private function switchAccountUrl(): ?string
    {
        $clientId = config('codesphere.client_id');

        if (! $clientId) {
            return null;
        }

        return rtrim((string) config('codesphere.url'), '/').'/oauth/logout?'.http_build_query([
            'client_id' => $clientId,
            'post_logout_redirect_uri' => route('login'),
        ]);
    }
}
