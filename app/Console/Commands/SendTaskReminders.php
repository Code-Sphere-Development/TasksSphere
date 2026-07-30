<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Notifications\TaskReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendTaskReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tasks:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send push notifications for due tasks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Scheduler: tasks:send-reminders command execution started.');

        $now = now();

        // Wir suchen nach Aufgaben, die fällig sind (due_at <= now),
        // noch nicht erledigt sind (completed_at is null)
        // und für die wir noch keine Benachrichtigung für diesen Termin gesendet haben.
        // Um es einfach zu halten, prüfen wir gegen ein (neu zu erstellendes) Feld last_notified_at.

        $query = Task::whereNotNull('due_at')
            ->where('due_at', '<=', $now)
            ->where('is_active', true)
            ->where('is_archived', false)
            ->whereNull('completed_at')
            ->where(function ($query) {
                $query->whereNull('last_notified_at')
                    ->orWhereColumn('last_notified_at', '<', 'due_at');
            })
            ->with('user');

        $this->info('Gefundene fällige Aufgaben: '.$query->count().' | '.now());
        Log::info('Scheduler: Found '.$query->count().' due tasks to process.');

        $query->chunk(100, function ($tasks) {
            foreach ($tasks as $task) {
                $user = $task->user;

                if ($user) {
                    if (! $user->notify_push) {
                        $task->update(['last_notified_at' => now()]);

                        continue;
                    }

                    $tokens = $user->routeNotificationForFcm();

                    if (! empty($tokens)) {
                        try {
                            $user->notify(new TaskReminderNotification($task));
                            $task->update(['last_notified_at' => now()]);
                            $this->info("Benachrichtigung für Task ID {$task->id} an Benutzer {$user->email} gesendet.");
                        } catch (\Exception $e) {
                            $this->error("Fehler beim Senden der Benachrichtigung für Task ID {$task->id}: ".$e->getMessage());
                            Log::error("FCM Error for task {$task->id}: ".$e->getMessage());
                        }
                    } else {
                        // Markieren als benachrichtigt, damit wir nicht hängen bleiben,
                        // auch wenn aktuell kein Token da ist.
                        $task->update(['last_notified_at' => now()]);
                        $this->warn("Kein FCM Token für Benutzer {$user->email} (Task ID {$task->id}) gefunden.");
                    }
                } else {
                    $task->update(['last_notified_at' => now()]);
                    $this->warn("Kein Benutzer für Task ID {$task->id} gefunden.");
                }
            }
        });

        Log::info('Scheduler: tasks:send-reminders command execution finished.');
    }
}
