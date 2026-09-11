<?php

namespace App\Enums;

/**
 * Woher eine Aufgabe stammt.
 *
 * Ersetzt das Titelpräfix "[Agent] ", mit dem sich der Agentenlauf im Homelab
 * bisher beholfen hat.
 */
enum TaskSource: string
{
    case Manual = 'manual';
    case Agent = 'agent';
    case Import = 'import';

    /**
     * Kurzbezeichnung für die Anzeige. Übersetzt über lang/*.json.
     */
    public function label(): string
    {
        return match ($this) {
            self::Manual => __('Selbst angelegt'),
            self::Agent => __('Agent'),
            self::Import => __('Import'),
        };
    }
}
