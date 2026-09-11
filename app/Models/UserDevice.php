<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_id',
        'fcm_token',
        'last_seen_at',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Ein frisch registriertes Gerät hat sich per Definition gerade gemeldet.
        // Ein ausdrücklich gesetztes null bleibt erhalten - das heißt "nie gesehen".
        static::creating(function (self $device) {
            if (! array_key_exists('last_seen_at', $device->getAttributes())) {
                $device->last_seen_at = now();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Geräte, die sich innerhalb des Schwellwerts beim Server gemeldet haben.
     *
     * Geräte ohne Zeitstempel gelten als still: lieber keine Benachrichtigung
     * als eine an ein Gerät, dessen Zustand wir nicht kennen.
     */
    public function scopeActive(Builder $query): Builder
    {
        $days = (int) config('tasks.device_stale_after_days', 180);

        return $query->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', now()->subDays($days));
    }

    /**
     * Zeitstempel auffrischen, gedrosselt.
     *
     * Die App schickt ihren FCM-Token bei jedem Request mit. Ohne Drossel
     * schriebe jeder Aufruf in die Tabelle.
     */
    public function markSeen(): void
    {
        $throttle = (int) config('tasks.device_seen_throttle_minutes', 60);

        if ($this->last_seen_at && $this->last_seen_at->gt(now()->subMinutes($throttle))) {
            return;
        }

        $this->forceFill(['last_seen_at' => now()])->save();
    }
}
