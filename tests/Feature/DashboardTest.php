<?php

namespace Tests\Feature;

use App\Enums\ApplicationStatus;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('dashboard'));
        $response->assertOk();
    }

    public function test_the_dashboard_shows_pipeline_statistics(): void
    {
        $user = User::factory()->create();
        JobApplication::factory()->for($user)->status(ApplicationStatus::Wishlist)->create();
        JobApplication::factory()->for($user)->status(ApplicationStatus::Applied)->count(2)->create();
        $interviewing = JobApplication::factory()->for($user)->status(ApplicationStatus::Interviewing)->create();
        Interview::factory()->for($interviewing)->create();
        Interview::factory()->create(); // someone else's

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('stats.total', 4)
                ->where('stats.responseRate', 33)
                ->where('stats.byStatus.1.count', 2)
                ->has('upcomingInterviews', 1));
    }
}
