<?php

namespace Tests\Feature\Applications;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class JobApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_see_the_board(): void
    {
        $this->get(route('applications.index'))->assertRedirect(route('login'));
    }

    public function test_the_board_only_shows_the_users_own_applications(): void
    {
        $user = User::factory()->create();
        JobApplication::factory()->for($user)->count(2)->create();
        JobApplication::factory()->count(3)->create();

        $this->actingAs($user)
            ->get(route('applications.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('applications/Board')
                ->has('applications', 2)
                ->has('statuses', count(ApplicationStatus::cases())));
    }

    public function test_a_user_can_create_an_application(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('applications.store'), [
            'company' => 'Acme',
            'position' => 'Laravel Developer',
            'status' => 'applied',
            'job_url' => 'https://acme.test/jobs/1',
        ]);

        $application = JobApplication::sole();
        $response->assertRedirect(route('applications.show', $application));

        $this->assertSame($user->id, $application->user_id);
        $this->assertSame(ApplicationStatus::Applied, $application->status);
        $this->assertNotNull($application->applied_at, 'Sent applications get an application date.');
    }

    public function test_new_applications_go_to_the_bottom_of_their_column(): void
    {
        $user = User::factory()->create();
        JobApplication::factory()->for($user)->create(['position_in_column' => 0]);
        JobApplication::factory()->for($user)->create(['position_in_column' => 1]);

        $this->actingAs($user)->post(route('applications.store'), [
            'company' => 'Acme',
            'position' => 'Developer',
            'status' => 'wishlist',
        ]);

        $this->assertSame(2, JobApplication::latest('id')->first()?->position_in_column);
    }

    public function test_creating_an_application_validates_the_input(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('applications.store'), [
                'company' => '',
                'status' => 'hired',
                'job_url' => 'not-a-url',
            ])
            ->assertSessionHasErrors(['company', 'position', 'status', 'job_url']);
    }

    public function test_a_user_can_view_and_update_their_application(): void
    {
        $application = JobApplication::factory()->create();

        $this->actingAs($application->user)
            ->get(route('applications.show', $application))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('applications/Show')
                ->where('application.id', $application->id));

        $this->actingAs($application->user)
            ->put(route('applications.update', $application), [
                'company' => 'New Co',
                'position' => 'Senior Developer',
                'status' => 'interviewing',
            ])
            ->assertRedirect(route('applications.show', $application));

        $application->refresh();
        $this->assertSame('New Co', $application->company);
        $this->assertSame(ApplicationStatus::Interviewing, $application->status);
    }

    public function test_users_cannot_access_applications_of_others(): void
    {
        $application = JobApplication::factory()->create();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)->get(route('applications.show', $application))->assertForbidden();
        $this->actingAs($intruder)->get(route('applications.edit', $application))->assertForbidden();
        $this->actingAs($intruder)->delete(route('applications.destroy', $application))->assertForbidden();
        $this->actingAs($intruder)
            ->patch(route('applications.move', $application), ['status' => 'offer', 'position' => 0])
            ->assertForbidden();

        $this->assertModelExists($application);
    }

    public function test_a_user_can_delete_their_application(): void
    {
        $application = JobApplication::factory()->create();

        $this->actingAs($application->user)
            ->delete(route('applications.destroy', $application))
            ->assertRedirect(route('applications.index'));

        $this->assertModelMissing($application);
    }
}
