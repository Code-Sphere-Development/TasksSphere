<?php

namespace App\Enums;

/**
 * Dringlichkeit einer Aufgabe.
 *
 * Der kleinere Wert ist die hoehere Dringlichkeit, damit eine aufsteigende
 * Sortierung das Wichtigste zuerst liefert.
 */
enum TaskPriority: int
{
    case Urgent = 1;
    case High = 2;
    case Medium = 3;
    case Low = 4;

    /**
     * Bezeichnung fuer die Anzeige. Uebersetzt ueber lang/*.json.
     */
    public function label(): string
    {
        return match ($this) {
            self::Urgent => __('Dringend'),
            self::High => __('Hoch'),
            self::Medium => __('Mittel'),
            self::Low => __('Niedrig'),
        };
    }

    /**
     * Tailwind-Klassen fuer die Markierung. Bewusst literal, damit der
     * Scanner sie findet - interpolierte Klassennamen erkennt Tailwind v4 nicht.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Urgent => 'bg-red-100 text-red-700 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800',
            self::High => 'bg-orange-100 text-orange-700 border-orange-200 dark:bg-orange-900/30 dark:text-orange-400 dark:border-orange-800',
            self::Medium => 'bg-yellow-100 text-yellow-700 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800',
            self::Low => 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-600',
        };
    }

    /**
     * Farbe der Schiene links an der Zeile und des Punktes in der Randspalte.
     * Literal, damit Tailwind sie findet.
     */
    public function accentClass(): string
    {
        return match ($this) {
            self::Urgent => 'bg-red-500',
            self::High => 'bg-amber-500',
            self::Medium => 'bg-sky-400',
            self::Low => 'bg-gray-300 dark:bg-gray-600',
        };
    }
}
