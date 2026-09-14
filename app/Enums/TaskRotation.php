<?php

namespace App\Enums;

use App\Support\Rotation\KeepLastRotation;
use App\Support\Rotation\LeastAssignedRotation;
use App\Support\Rotation\LeastCompletedRotation;
use App\Support\Rotation\RandomRotation;
use App\Support\Rotation\RotationStrategy;

/**
 * Wie die naechste zustaendige Person bestimmt wird, wenn ein Termin einer
 * wiederkehrenden Aufgabe erledigt ist.
 */
enum TaskRotation: string
{
    case Random = 'random';
    case LeastAssigned = 'least_assigned';
    case LeastCompleted = 'least_completed';
    case KeepLast = 'keep_last';

    public function label(): string
    {
        return match ($this) {
            self::Random => __('Zufällig'),
            self::LeastAssigned => __('Am seltensten zuständig'),
            self::LeastCompleted => __('Am seltensten erledigt'),
            self::KeepLast => __('Zuständige Person bleibt'),
        };
    }

    public function strategy(): RotationStrategy
    {
        return match ($this) {
            self::Random => new RandomRotation,
            self::LeastAssigned => new LeastAssignedRotation,
            self::LeastCompleted => new LeastCompletedRotation,
            self::KeepLast => new KeepLastRotation,
        };
    }
}
