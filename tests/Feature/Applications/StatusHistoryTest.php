<?php

namespace Tests\Feature\Applications;

use App\Actions\ResetDemoAccount;
use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\StatusChange;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class StatusHistoryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<array{string|null, string}>
     */
    private function path(JobApplication $application): array
    {
        return $application->statusChanges()->get()
            ->map(fn (StatusChange $change) => [$change->from_status?->value, $change->to_status->value])
            ->all();
    }

    public function test_creating_an_application_starts_its_history(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('applications.store'), [
            'company' => 'Acme',
            'position' => 'Developer',
            'status' => 'applied',
        ]);

        $application = $user->jobApplications()->sole();
        $this->assertSame([[null, 'applied']], $this->path($application));
    }

    public function test_drag_and_drop_between_columns_is_recorded(): void
    {
        $application = JobApplication::factory()->status(ApplicationStatus::Applied)->create();

        $this->travel(2)->days();

        $this->actingAs($application->user)
            ->patch(route('applications.move', $application), ['status' => 'interviewing', 'position' => 0])
            ->assertRedirect();

        $this->assertSame([[null, 'applied'], ['applied', 'interviewing']], $this->path($application));
        $this->assertTrue($application->statusChanges()->get()->last()?->changed_at->isToday());
    }

    public function test_reordering_within_a_column_is_not_a_status_change(): void
    {
        $application = JobApplication::factory()->status(ApplicationStatus::Applied)->create();
        JobApplication::factory()->for($application->user)->status(ApplicationStatus::Applied)->create();

        $this->actingAs($application->user)
            ->patch(route('applications.move', $application), ['status' => 'applied', 'position' => 1]);

        $this->assertSame([[null, 'applied']], $this->path($application));
    }

    public function test_changing_the_status_from_the_edit_form_is_recorded(): void
    {
        $application = JobApplication::factory()->status(ApplicationStatus::Interviewing)->create();

        $this->actingAs($application->user)->put(route('applications.update', $application), [
            'company' => $application->company,
            'position' => $application->position,
            'status' => 'rejected',
        ]);

        $this->assertSame([[null, 'interviewing'], ['interviewing', 'rejected']], $this->path($application));
    }

    public function test_editing_other_fields_does_not_add_history(): void
    {
        $application = JobApplication::factory()->status(ApplicationStatus::Offer)->create();

        $this->actingAs($application->user)->put(route('applications.update', $application), [
            'company' => 'Renamed Co',
            'position' => $application->position,
            'status' => 'offer',
        ]);

        $this->assertSame([[null, 'offer']], $this->path($application));
    }

    public function test_the_history_is_deleted_with_the_application(): void
    {
        $application = JobApplication::factory()->create();

        $this->actingAs($application->user)->delete(route('applications.destroy', $application));

        $this->assertSame(0, StatusChange::query()->count());
    }

    public function test_the_application_page_shows_the_timeline(): void
    {
        $application = JobApplication::factory()->status(ApplicationStatus::Wishlist)->create();
        $application->update(['status' => ApplicationStatus::Applied]);

        $this->actingAs($application->user)
            ->get(route('applications.show', $application))
            ->assertInertia(fn (Assert $page) => $page
                ->has('application.status_changes', 2)
                ->where('application.status_changes.0.from_status', null)
                ->where('application.status_changes.0.to_status', 'wishlist')
                ->where('application.status_changes.1.from_status', 'wishlist')
                ->where('application.status_changes.1.to_status', 'applied')
                ->where('application.status_changes.1.to_label', 'Applied'));
    }

    public function test_the_demo_board_comes_with_a_believable_history(): void
    {
        config(['demo.enabled' => true]);
        $user = app(ResetDemoAccount::class)->handle();

        $offer = $user->jobApplications()->where('status', ApplicationStatus::Offer)->sole();
        $changes = $offer->statusChanges()->get();

        $this->assertSame(
            [[null, 'wishlist'], ['wishlist', 'applied'], ['applied', 'interviewing'], ['interviewing', 'offer']],
            $this->path($offer),
        );
        $this->assertTrue($changes->every(fn (StatusChange $change) => $change->changed_at->isPast()));
        $timestamps = $changes->map(fn (StatusChange $change) => $change->changed_at->getTimestamp())->all();
        $sorted = $timestamps;
        sort($sorted);
        $this->assertSame($sorted, $timestamps);
        $this->assertTrue($offer->created_at?->equalTo($changes->first()?->changed_at));

        // Every demo card ends its history in its current column.
        foreach ($user->jobApplications()->with('statusChanges')->get() as $application) {
            $this->assertSame($application->status, $application->statusChanges->last()?->to_status);
        }
    }
}
