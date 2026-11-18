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
 * Sagt der zustaendigen Person sofort Bescheid, nicht erst zur Frist.
 *
 * Wie TaskReminderNotification ueber die Warteschlange, damit ein nicht
 * erreichbares FCM das Speichern der Aufgabe nicht aufhaelt.
 */
class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(protected Task $task) {}

    /**
     * Erst beim Versand geprueft: Bis der Job laeuft, kann die Person Push
     * abgeschaltet oder ihr letztes Geraet verloren haben.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (! $notifiable->notify_push || empty($notifiable->routeNotificationForFcm())) {
            return [];
        }

        return [FcmChannel::class];
    }

    public function toFcm(object $notifiable): FcmMessage
    {
        $lang = $notifiable->language ?? 'de';
        $title = $lang === 'en' ? 'New task for you' : 'Neue Aufgabe für dich';

        return (new FcmMessage(notification: new FcmNotification(
            title: $title,
            body: $this->task->title,
        )))
            ->data(['task_id' => (string) $this->task->id, 'type' => 'assigned']);
    }
}
