<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'language',
        'timezone',
        'fcm_token',
        'notify_email',
        'notify_push',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'name',
        'profile_photo_url',
    ];

    /**
     * Get the user's full name.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn () => trim($this->first_name.' '.$this->last_name),
        );
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_email' => 'boolean',
            'notify_push' => 'boolean',
        ];
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    /**
     * Route notifications for the FCM channel.
     */
    public function routeNotificationForFcm(): array|string|null
    {
        return $this->devices()
            ->pluck('fcm_token')
            ->toArray();
    }

    /**
     * Update or create an FCM token for this user.
     */
    public function updateFcmToken(?string $fcmToken, ?string $deviceId = null): void
    {
        if (empty($fcmToken)) {
            return;
        }

        // Falls dieser Token bereits bei einem anderen Benutzer registriert ist, dort entfernen
        UserDevice::where('fcm_token', $fcmToken)
            ->where('user_id', '!=', $this->id)
            ->delete();

        if ($deviceId) {
            $this->devices()->updateOrCreate(
                ['device_id' => $deviceId],
                ['fcm_token' => $fcmToken]
            );
        } else {
            // Wenn keine device_id vorhanden ist, nutzen wir den fcm_token selbst als Identifikator
            $this->devices()->updateOrCreate(
                ['fcm_token' => $fcmToken],
                ['device_id' => null]
            );
        }

        // Abwärtskompatibilität: Einzelnes Token im User-Model ebenfalls aktualisieren
        if ($this->fcm_token !== $fcmToken) {
            $this->update(['fcm_token' => $fcmToken]);
        }
    }
}
