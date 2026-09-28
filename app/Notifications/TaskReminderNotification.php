<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

/**
 * Der Versand laeuft ueber die Warteschlange: Ein nicht erreichbares FCM
 * darf weder den Aufruf der Schnittstelle noch den Erinnerungslauf aufhalten.
 * Fehlgeschlagene Zustellungen landen in failed_jobs statt in einer
 * Fehlerantwort.
 */
class TaskReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $task;

    /**
     * Create a new notification instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    /**
     * Get the FCM representation of the notification.
     */
    public function toFcm(object $notifiable): FcmMessage
    {
        $lang = $notifiable->language ?? 'de';
        $title = $lang === 'en' ? 'Task due!' : 'Aufgabe fällig!';

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $this->task->title,
        )))
            ->data(['task_id' => (string) $this->task->id]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->task->id,
            'title' => $this->task->title,
        ];
    }
}
