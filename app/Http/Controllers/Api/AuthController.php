<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AccountSphere\PasswordLogin;
use App\Support\AccountSphere\PasswordLoginFailed;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Konten entstehen nur noch in AccountSphere.
     */
    public function register()
    {
        return $this->managedByAccountSphere('/register');
    }

    /**
     * Passwoerter verwaltet AccountSphere.
     */
    public function forgotPassword()
    {
        return $this->managedByAccountSphere('/forgot-password');
    }

    public function login(Request $request, PasswordLogin $passwordLogin)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required',
        ]);

        try {
            $profile = $passwordLogin->attempt($request->email, $request->password);
        } catch (PasswordLoginFailed $e) {
            if ($e->status === 422) {
                throw ValidationException::withMessages(['email' => [$e->getMessage()]]);
            }

            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        $user = User::firstOrCreate(['codesphere_id' => $profile['id']]);
        $user->syncFromCodeSphere($profile);

        // Falls FCM Token im Header oder Body mitgesendet wird, auch beim Login speichern
        $fcmToken = $request->header('X-FCM-Token') ?? $request->fcm_token;
        $deviceId = $request->header('X-Device-ID') ?? $request->device_id;
        $timezone = $request->header('X-Timezone') ?? $request->timezone;

        if ($timezone) {
            $user->update(['timezone' => $timezone]);
        }

        if ($fcmToken) {
            $user->updateFcmToken($fcmToken, $deviceId);
        }

        return response()->json([
            'token' => $user->createToken($request->device_name)->plainTextToken,
            'user' => $user,
        ]);
    }

    private function managedByAccountSphere(string $path)
    {
        $url = rtrim((string) config('codesphere.url'), '/').$path;

        return response()->json([
            'message' => 'Konten werden in CodeSphere verwaltet.',
            'url' => $url,
        ], 410);
    }

    public function logout(Request $request)
    {
        $accessToken = $request->user()->currentAccessToken();

        $accessToken->delete();

        return response()->json(['message' => 'Abgemeldet']);
    }

    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_id' => 'nullable|string',
            'timezone' => 'nullable|string|timezone',
        ]);

        if ($request->timezone) {
            $request->user()->update(['timezone' => $request->timezone]);
        }

        $request->user()->updateFcmToken(
            $request->fcm_token,
            $request->device_id
        );

        return response()->json(['message' => 'FCM Token aktualisiert']);
    }
}
