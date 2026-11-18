<?php

use App\Http\Middleware\HandleFcmTokenHeader;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SetLocale::class);
        $middleware->append(HandleFcmTokenHeader::class);
        $middleware->trustProxies(at: [
            '127.0.0.1',
            '192.168.0.0/16',
            '10.0.0.0/8',
            '172.16.0.0/12',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        // Die Mobil-App verwirft ihr Token bei jedem 401 und zwingt zur
        // Neuanmeldung. Damit sich nachvollziehen laesst, warum ein Token
        // abgelehnt wurde, wird festgehalten, welches es war und ob es noch
        // existiert. Nur die ID vor dem "|", nie das Geheimnis.
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            $bearer = $request->bearerToken();

            if ($bearer && $request->is('api/*')) {
                $tokenId = str_contains($bearer, '|') ? (int) strstr($bearer, '|', true) : null;

                Log::info('API: Token abgelehnt', [
                    'token_id' => $tokenId,
                    'token_exists' => $tokenId ? PersonalAccessToken::whereKey($tokenId)->exists() : null,
                    'path' => $request->path(),
                    // Kopfzeilen kommen ungeprueft vom Client: gekuerzt loggen.
                    'device_id' => Str::limit((string) $request->header('X-Device-ID'), 64, ''),
                    'user_agent' => Str::limit((string) $request->userAgent(), 120),
                ]);
            }

            return null;
        });
    })->create();
