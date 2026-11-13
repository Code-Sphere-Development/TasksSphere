<?php

namespace App\Models;

use CodeSphere\OAuth\Concerns\HasCodeSphereIdentity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * Lokaler Stub eines AccountSphere-Kontos.
 *
 * first_name, last_name, email und language sind ein Snapshot, den jeder
 * Login aus AccountSphere ueberschreibt (SyncProfileAfterCodeSphereLogin).
 */
class User extends Authenticatable
{
    use HasApiTokens;
    use HasCodeSphereIdentity;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'codesphere_id',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'first_name',
        'last_name',
        'email',
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
        'access_token',
        'refresh_token',
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
     * Ueberschreibt HasCodeSphereIdentity: Der Trait liest aus der Session und
     * lieferte damit fuer jeden Nutzer den Namen des gerade eingeloggten.
     */
    public function getNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Siehe getNameAttribute().
     */
    public function getEmailAttribute(?string $value): ?string
    {
        return $value;
    }

    /**
     * Initialen-Avatar, wie ihn Jetstream ohne hochgeladenes Profilfoto lieferte.
     */
    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::get(function (): string {
            $initials = collect(explode(' ', $this->name))
                ->filter()
                ->map(fn (string $segment) => mb_substr($segment, 0, 1))
                ->join(' ');

            return 'https://ui-avatars.com/api/?name='.urlencode($initials).'&color=7F9CF5&background=EBF4FF';
        });
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'token_expires_at' => 'datetime',
            'notify_email' => 'boolean',
            'notify_push' => 'boolean',
        ];
    }

    /**
     * Uebernimmt Name, E-Mail und Sprache aus dem AccountSphere-Profil.
     *
     * AccountSphere kennt nur einen Gesamtnamen; geteilt wird am ersten
     * Leerzeichen, wie schon beim Aufteilen der alten name-Spalte.
     *
     * @param  array<string, mixed>  $profile  der user-Teil der AccountSphere-Antwort
     */
    public function syncFromCodeSphere(array $profile): void
    {
        $attributes = array_filter([
            'email' => $profile['email'] ?? null,
            'language' => $profile['language'] ?? null,
        ], fn ($value) => $value !== null);

        $name = trim((string) ($profile['name'] ?? ''));

        if ($name !== '') {
            $parts = explode(' ', $name, 2);
            $attributes['first_name'] = $parts[0];
            $attributes['last_name'] = $parts[1] ?? null;
        }

        $this->forceFill($attributes)->save();
    }

    public function households()
    {
        return $this->belongsToMany(Household::class)->withPivot('role')->withTimestamps();
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
            ->active()
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
            $device = $this->devices()->updateOrCreate(
                ['device_id' => $deviceId],
                ['fcm_token' => $fcmToken]
            );
        } else {
            // Wenn keine device_id vorhanden ist, nutzen wir den fcm_token selbst als Identifikator
            $device = $this->devices()->updateOrCreate(
                ['fcm_token' => $fcmToken],
                ['device_id' => null]
            );
        }

        // Das Gerät hat sich soeben gemeldet - das ist der einzige Beleg dafür,
        // dass die App dort noch angemeldet ist.
        $device->markSeen();

        // Abwärtskompatibilität: Einzelnes Token im User-Model ebenfalls aktualisieren
        if ($this->fcm_token !== $fcmToken) {
            $this->update(['fcm_token' => $fcmToken]);
        }
    }
}
