<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleFcmTokenHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Nur verarbeiten, wenn der Benutzer eingeloggt ist
        if (auth()->check()) {
            // Zeitzone aktualisieren, falls mitgesendet
            $timezone = $request->header('X-Timezone');
            if ($timezone && auth()->user()->timezone !== $timezone) {
                auth()->user()->update(['timezone' => $timezone]);
            }

            // FCM Token verarbeiten
            if ($request->hasHeader('X-FCM-Token')) {
                auth()->user()->updateFcmToken(
                    $request->header('X-FCM-Token'),
                    $request->header('X-Device-ID')
                );
            }
        }

        return $response;
    }
}
