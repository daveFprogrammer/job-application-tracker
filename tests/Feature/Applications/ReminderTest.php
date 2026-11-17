<?php

namespace Tests\Feature\Applications;

use App\Jobs\SendReminder;
use App\Models\JobApplication;
use App\Models\Reminder;
use App\Models\User;
use App\Notifications\ReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_set_a_reminder(): void
    {
        $application = JobApplication::factory()->create();

        $this->actingAs($application->user)
            ->post(route('reminders.store', $application), [
                'remind_at' => now()->addDays(3)->toDateTimeString(),
                'message' => 'Follow up',
            ])
            ->assertRedirect();

        $this->assertSame('Follow up', Reminder::sole()->message);
    }

    public function test_reminders_cannot_be_set_in_the_past(): void
    {
        $application = JobApplication::factory()->create();

        $this->actingAs($application->user)
            ->post(route('reminders.store', $application), [
                'remind_at' => now()->subHour()->toDateTimeString(),
                'message' => 'Too late',
            ])
            ->assertSessionHasErrors('remind_at');
    }

    public function test_users_cannot_delete_reminders_of_others(): void
    {
        $reminder = Reminder::factory()->create();

        $this->actingAs(User::factory()->create())
            ->delete(route('reminders.destroy', $reminder))
            ->assertForbidden();
    }

    public function test_the_command_queues_only_due_reminders_once(): void
    {
        Queue::fake();

        $due = Reminder::factory()->due()->create();
        Reminder::factory()->create(['remind_at' => now()->addDay()]);
        Reminder::factory()->due()->create(['queued_at' => now()->subMinute()]);

        $this->artisan('reminders:dispatch')->assertSuccessful();
        $this->artisan('reminders:dispatch')->assertSuccessful();

        Queue::assertPushed(SendReminder::class, 1);
        Queue::assertPushed(SendReminder::class, fn (SendReminder $job) => $job->reminder->is($due));
        $this->assertNotNull($due->refresh()->queued_at);
    }

    public function test_the_job_emails_the_owner_and_marks_the_reminder_as_sent(): void
    {
        Notification::fake();

        $reminder = Reminder::factory()->due()->create();
        $owner = $reminder->jobApplication->user;

        (new SendReminder($reminder))->handle();

        Notification::assertSentTo($owner, ReminderNotification::class);
        $this->assertNotNull($reminder->refresh()->sent_at);
    }

    public function test_the_job_does_not_send_the_same_reminder_twice(): void
    {
        Notification::fake();

        $reminder = Reminder::factory()->due()->create(['sent_at' => now()]);

        (new SendReminder($reminder))->handle();

        Notification::assertNothingSent();
    }

    public function test_the_reminder_email_links_to_the_application(): void
    {
        $reminder = Reminder::factory()->create(['message' => 'Call back Acme']);

        $mail = (new ReminderNotification($reminder))->toMail($reminder->jobApplication->user);

        $this->assertStringContainsString($reminder->jobApplication->company, (string) $mail->subject);
        $this->assertContains('Call back Acme', $mail->introLines);
        $this->assertSame(route('applications.show', $reminder->jobApplication), $mail->actionUrl);
    }
}
