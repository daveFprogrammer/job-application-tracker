<?php

namespace Tests\Feature\Applications;

use App\Enums\ApplicationStatus;
use App\Enums\WorkMode;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BoardFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();

        $board = [
            ['Nebula Labs', 'Laravel Developer', 'Milano', WorkMode::Hybrid, ApplicationStatus::Wishlist],
            ['Verdecloud', 'Backend Engineer', 'Remote (EU)', WorkMode::Remote, ApplicationStatus::Applied],
            ['Kite Studio', 'Vue.js Developer', 'Milano', WorkMode::Onsite, ApplicationStatus::Interviewing],
            ['Monolith Corp', 'PHP Engineer', 'Roma', WorkMode::Remote, ApplicationStatus::Rejected],
            ['100%_Agency', 'Web Developer', 'Torino', WorkMode::Hybrid, ApplicationStatus::Applied],
        ];

        foreach ($board as [$company, $position, $location, $workMode, $status]) {
            JobApplication::factory()->for($this->user)->status($status)->create([
                'company' => $company,
                'position' => $position,
                'location' => $location,
                'work_mode' => $workMode,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $query
     * @return list<string>
     */
    private function companies(array $query = []): array
    {
        $companies = [];

        $this->actingAs($this->user)
            ->get(route('applications.index', $query))
            ->assertOk()
            ->assertInertia(function (Assert $page) use (&$companies) {
                $page->component('applications/Board')->where('total', 5);
                $companies = collect($page->toArray()['props']['applications'])->pluck('company')->sort()->values()->all();
            });

        return $companies;
    }

    public function test_without_filters_the_whole_board_is_shown(): void
    {
        $this->actingAs($this->user)
            ->get(route('applications.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications', 5)
                ->has('workModes', count(WorkMode::cases()))
                ->where('filters', ['search' => '', 'work_mode' => null, 'hide_rejected' => false]));
    }

    public function test_search_matches_company_role_and_location_case_insensitively(): void
    {
        $this->assertSame(['Nebula Labs'], $this->companies(['search' => 'NEBULA']));
        $this->assertSame(['Kite Studio'], $this->companies(['search' => 'vue.js']));
        $this->assertSame(['Kite Studio', 'Nebula Labs'], $this->companies(['search' => 'milano']));
        $this->assertSame([], $this->companies(['search' => 'nothing like this']));
    }

    public function test_search_treats_like_wildcards_literally(): void
    {
        $this->assertSame(['100%_Agency'], $this->companies(['search' => '%_']));
        $this->assertSame([], $this->companies(['search' => '_x_']));
    }

    public function test_the_board_can_be_filtered_by_work_mode(): void
    {
        $this->assertSame(['Monolith Corp', 'Verdecloud'], $this->companies(['work_mode' => 'remote']));
    }

    public function test_the_rejected_column_can_be_hidden(): void
    {
        $this->assertNotContains('Monolith Corp', $this->companies(['hide_rejected' => 1]));
        $this->assertCount(4, $this->companies(['hide_rejected' => 1]));
    }

    public function test_filters_can_be_combined_and_are_echoed_back(): void
    {
        $this->actingAs($this->user)
            ->get(route('applications.index', ['search' => ' remote ', 'work_mode' => 'remote', 'hide_rejected' => 1]))
            ->assertInertia(fn (Assert $page) => $page
                ->has('applications', 1)
                ->where('applications.0.company', 'Verdecloud')
                ->where('filters', ['search' => 'remote', 'work_mode' => 'remote', 'hide_rejected' => true]));
    }

    public function test_invalid_filter_values_are_ignored(): void
    {
        $this->assertCount(5, $this->companies(['work_mode' => 'spaceship', 'hide_rejected' => 'maybe']));
    }

    public function test_filters_never_show_other_users_applications(): void
    {
        JobApplication::factory()->create(['company' => 'Nebula Labs Clone', 'location' => 'Milano']);

        $this->assertSame(['Kite Studio', 'Nebula Labs'], $this->companies(['search' => 'milano']));
    }
}
