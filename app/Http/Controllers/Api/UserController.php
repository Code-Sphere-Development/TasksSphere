<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Felder, die AccountSphere besitzt. Clients wie die Mobile-App schicken sie
     * beim Speichern des Profils weiterhin mit; unveraendert sind sie harmlos.
     */
    private const MANAGED_BY_ACCOUNTSPHERE = ['first_name', 'last_name', 'email'];

    public function show(Request $request)
    {
        return $request->user();
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'first_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255'],
            'password' => ['nullable', 'string'],
            'language' => ['nullable', 'string', 'in:de,en'],
        ]);

        $changed = collect(self::MANAGED_BY_ACCOUNTSPHERE)
            ->filter(fn (string $field) => array_key_exists($field, $validated)
                && (string) $validated[$field] !== (string) $user->getAttribute($field));

        if (! empty($validated['password'])) {
            $changed->push('password');
        }

        if ($changed->isNotEmpty()) {
            $message = 'Name, E-Mail und Passwort werden in CodeSphere geändert: '
                .rtrim((string) config('codesphere.url'), '/').'/profile';

            throw ValidationException::withMessages(
                $changed->mapWithKeys(fn (string $field) => [$field => [$message]])->all()
            );
        }

        if (array_key_exists('language', $validated) && $validated['language'] !== null) {
            $user->update(['language' => $validated['language']]);
        }

        return response()->json([
            'message' => 'Profil aktualisiert',
            'user' => $user->fresh(),
        ]);
    }
}
