<?php

namespace App\Notifications;

use App\Models\Reminder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReminderNotification extends Notification
{
    public function __construct(public Reminder $reminder) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $application = $this->reminder->jobApplication;

        return (new MailMessage)
            ->subject("Reminder: {$application->position} at {$application->company}")
            ->greeting('Heads up!')
            ->line($this->reminder->message)
            ->action('Open application', route('applications.show', $application))
            ->line('Good luck with your job search!');
    }
}
