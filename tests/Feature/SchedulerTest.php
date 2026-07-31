<?php

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;

test('tasks send reminders command is scheduled', function () {
    $schedule = app(Schedule::class);

    $events = collect($schedule->events())->filter(function (Event $event) {
        return str_contains($event->command, 'tasks:send-reminders');
    });

    $this->assertCount(1, $events, 'Der Befehl tasks:send-reminders ist nicht im Scheduler registriert.');

    $event = $events->first();
    $this->assertEquals('* * * * *', $event->expression);
});
