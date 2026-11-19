<?php

namespace App\Notifications;

use App\Models\Reminder;
use App\Models\User;
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
        // The demo account has a fake address: reminders are marked as sent, never emailed.
        if ($notifiable instanceof User && $notifiable->is_demo) {
            return [];
        }

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
