<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Koppelt ein Konto aus der Zeit vor AccountSphere an dessen CodeSphere-Konto.
 *
 * Beim naechsten SSO-Login findet das Paket den Nutzer ueber codesphere_id und
 * meldet ihn im Altkonto an - mit allen Aufgaben, Haushalten und Geraeten.
 */
class LinkCodeSphereAccount extends Command
{
    /**
     * Tabellen und Spalten, die auf users.id zeigen. Ein Stub mit Eintraegen
     * hier wird nie geloescht.
     */
    private const USER_REFERENCES = [
        'tasks' => ['user_id', 'assigned_to'],
        'task_lists' => ['user_id'],
        'task_assignments' => ['user_id', 'assigned_by'],
        'task_completions' => ['completed_by'],
        'households' => ['owner_id'],
        'household_user' => ['user_id'],
        'user_devices' => ['user_id'],
    ];

    protected $signature = 'users:link-codesphere
        {user : Lokale ID oder E-Mail des Altkontos}
        {codesphere_id : ID des Kontos in AccountSphere}';

    protected $description = 'Koppelt ein bestehendes Konto an ein AccountSphere-Konto';

    public function handle(): int
    {
        $user = $this->findUser((string) $this->argument('user'));

        if (! $user) {
            $this->error('Kein Konto gefunden.');

            return self::FAILURE;
        }

        $codesphereId = (int) $this->argument('codesphere_id');

        if ($user->codesphere_id === $codesphereId) {
            $this->info("Konto {$user->id} ist bereits gekoppelt.");

            return self::SUCCESS;
        }

        if ($user->codesphere_id !== null) {
            $this->error("Konto {$user->id} ist bereits an CodeSphere-ID {$user->codesphere_id} gekoppelt.");

            return self::FAILURE;
        }

        $stub = User::where('codesphere_id', $codesphereId)->first();

        if ($stub && $this->ownsData($stub)) {
            $this->error("CodeSphere-ID {$codesphereId} gehoert bereits Konto {$stub->id}, das eigene Daten hat. Bitte von Hand zusammenfuehren.");

            return self::FAILURE;
        }

        DB::transaction(function () use ($user, $stub, $codesphereId) {
            if ($stub) {
                $stub->tokens()->delete();
                $stub->delete();
            }

            $user->forceFill(['codesphere_id' => $codesphereId])->save();
        });

        $this->info("Konto {$user->id} ({$user->email}) ist jetzt an CodeSphere-ID {$codesphereId} gekoppelt.");

        if ($stub) {
            $this->line("Das leere Konto {$stub->id} aus einem frueheren SSO-Login wurde entfernt.");
        }

        return self::SUCCESS;
    }

    private function findUser(string $identifier): ?User
    {
        if (ctype_digit($identifier)) {
            return User::find((int) $identifier);
        }

        $matches = User::where('email', $identifier)->whereNull('codesphere_id')->get();

        if ($matches->count() > 1) {
            $this->warn('Mehrere ungekoppelte Konten mit dieser E-Mail, bitte die lokale ID angeben.');

            return null;
        }

        return $matches->first();
    }

    private function ownsData(User $user): bool
    {
        foreach (self::USER_REFERENCES as $table => $columns) {
            $owns = DB::table($table)
                ->where(function ($query) use ($columns, $user) {
                    foreach ($columns as $column) {
                        $query->orWhere($column, $user->id);
                    }
                })
                ->exists();

            if ($owns) {
                return true;
            }
        }

        return false;
    }
}
