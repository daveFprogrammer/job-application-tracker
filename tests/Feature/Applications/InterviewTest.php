<?php

namespace Tests\Feature\Applications;

use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduling_an_interview_creates_a_reminder_the_day_before(): void
    {
        $this->travelTo('2026-11-17 09:00:00');
        $application = JobApplication::factory()->create(['company' => 'Acme']);

        $this->actingAs($application->user)
            ->post(route('interviews.store', $application), [
                'scheduled_at' => '2026-11-20 10:00',
                'type' => 'video',
                'remind' => '1',
            ])
            ->assertRedirect();

        $interview = Interview::sole();
        $this->assertSame('2026-11-20 10:00:00', $interview->scheduled_at->toDateTimeString());

        $reminder = $interview->reminder;
        $this->assertNotNull($reminder);
        $this->assertSame('2026-11-19 10:00:00', $reminder->remind_at->toDateTimeString());
        $this->assertStringContainsString('Acme', $reminder->message);
    }

    public function test_an_interview_closer_than_a_day_is_reminded_one_hour_before(): void
    {
        $this->travelTo('2026-11-17 09:00:00');
        $application = JobApplication::factory()->create();

        $this->actingAs($application->user)->post(route('interviews.store', $application), [
            'scheduled_at' => '2026-11-17 15:00',
            'type' => 'phone',
            'remind' => '1',
        ]);

        $this->assertSame('2026-11-17 14:00:00', Interview::sole()->reminder?->remind_at->toDateTimeString());
    }

    public function test_no_reminder_is_created_when_not_requested(): void
    {
        $application = JobApplication::factory()->create();

        $this->actingAs($application->user)->post(route('interviews.store', $application), [
            'scheduled_at' => now()->addWeek()->toDateTimeString(),
            'type' => 'onsite',
        ]);

        $this->assertNull(Interview::sole()->reminder);
    }

    public function test_rescheduling_an_interview_moves_its_reminder(): void
    {
        $this->travelTo('2026-11-17 09:00:00');
        $application = JobApplication::factory()->create();
        $this->actingAs($application->user)->post(route('interviews.store', $application), [
            'scheduled_at' => '2026-11-20 10:00', 'type' => 'video', 'remind' => '1',
        ]);
        $interview = Interview::sole();

        $this->actingAs($application->user)->put(route('interviews.update', $interview), [
            'scheduled_at' => '2026-11-25 16:30', 'type' => 'technical', 'remind' => '1',
        ]);

        $this->assertSame(1, $application->reminders()->count());
        $this->assertSame('2026-11-24 16:30:00', $interview->refresh()->reminder?->remind_at->toDateTimeString());
    }

    public function test_users_cannot_manage_interviews_of_others(): void
    {
        $interview = Interview::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->post(route('interviews.store', $interview->jobApplication), [
                'scheduled_at' => now()->addDay()->toDateTimeString(), 'type' => 'video',
            ])
            ->assertForbidden();
        $this->actingAs($intruder)->delete(route('interviews.destroy', $interview))->assertForbidden();

        $this->assertSame(1, Interview::count());
    }
}
