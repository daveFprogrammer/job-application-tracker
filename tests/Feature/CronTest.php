<?php

namespace Tests\Feature;

use App\Models\Reminder;
use App\Notifications\ReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CronTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_endpoint_is_disabled_without_a_secret(): void
    {
        config(['services.cron.secret' => null]);

        $this->postJson(route('cron.run'))->assertNotFound();
    }

    public function test_the_endpoint_requires_the_right_token(): void
    {
        config(['services.cron.secret' => 'top-secret']);

        $this->postJson(route('cron.run'))->assertUnauthorized();
        $this->postJson(route('cron.run'), [], ['Authorization' => 'Bearer wrong'])->assertUnauthorized();
    }

    public function test_the_endpoint_sends_due_reminders(): void
    {
        Notification::fake();
        config(['services.cron.secret' => 'top-secret']);

        $reminder = Reminder::factory()->due()->create();

        $this->postJson(route('cron.run'), [], ['Authorization' => 'Bearer top-secret'])->assertOk();

        Notification::assertSentTo($reminder->jobApplication->user, ReminderNotification::class);
        $this->assertNotNull($reminder->refresh()->sent_at);
    }
}
