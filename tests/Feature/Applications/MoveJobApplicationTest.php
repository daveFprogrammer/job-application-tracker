<?php

namespace Tests\Feature\Applications;

use App\Enums\ApplicationStatus;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MoveJobApplicationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return list<JobApplication>
     */
    private function column(User $user, ApplicationStatus $status, int $count): array
    {
        $cards = [];

        for ($i = 0; $i < $count; $i++) {
            $cards[] = JobApplication::factory()->for($user)->status($status)->create(['position_in_column' => $i]);
        }

        return $cards;
    }

    /**
     * @return list<int>
     */
    private function idsIn(User $user, ApplicationStatus $status): array
    {
        /** @var list<int> */
        return $user->jobApplications()
            ->where('status', $status)
            ->orderBy('position_in_column')
            ->pluck('id')
            ->all();
    }

    public function test_a_card_can_be_moved_to_another_column(): void
    {
        $user = User::factory()->create();
        [$a, $b, $c] = $this->column($user, ApplicationStatus::Applied, 3);
        [$x, $y] = $this->column($user, ApplicationStatus::Interviewing, 2);

        $this->actingAs($user)
            ->patch(route('applications.move', $b), ['status' => 'interviewing', 'position' => 1])
            ->assertRedirect();

        $this->assertSame([$a->id, $c->id], $this->idsIn($user, ApplicationStatus::Applied));
        $this->assertSame([$x->id, $b->id, $y->id], $this->idsIn($user, ApplicationStatus::Interviewing));
        $this->assertSame([0, 1], $user->jobApplications()->where('status', 'applied')->orderBy('position_in_column')->pluck('position_in_column')->all());
    }

    public function test_a_card_can_be_reordered_inside_its_column(): void
    {
        $user = User::factory()->create();
        [$a, $b, $c] = $this->column($user, ApplicationStatus::Wishlist, 3);

        $this->actingAs($user)
            ->patch(route('applications.move', $c), ['status' => 'wishlist', 'position' => 0])
            ->assertRedirect();

        $this->assertSame([$c->id, $a->id, $b->id], $this->idsIn($user, ApplicationStatus::Wishlist));
    }

    public function test_an_out_of_range_position_drops_the_card_at_the_bottom(): void
    {
        $user = User::factory()->create();
        [$a] = $this->column($user, ApplicationStatus::Wishlist, 1);
        [$x, $y] = $this->column($user, ApplicationStatus::Offer, 2);

        $this->actingAs($user)
            ->patch(route('applications.move', $a), ['status' => 'offer', 'position' => 99]);

        $this->assertSame([$x->id, $y->id, $a->id], $this->idsIn($user, ApplicationStatus::Offer));
    }

    public function test_moving_out_of_the_wishlist_sets_the_application_date(): void
    {
        $user = User::factory()->create();
        [$a] = $this->column($user, ApplicationStatus::Wishlist, 1);

        $this->actingAs($user)->patch(route('applications.move', $a), ['status' => 'applied', 'position' => 0]);

        $this->assertSame(now()->toDateString(), $a->refresh()->applied_at?->toDateString());
    }

    public function test_moving_does_not_touch_other_users_boards(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        [$a] = $this->column($user, ApplicationStatus::Wishlist, 1);
        [$o1, $o2] = $this->column($other, ApplicationStatus::Applied, 2);

        $this->actingAs($user)->patch(route('applications.move', $a), ['status' => 'applied', 'position' => 0]);

        $this->assertSame([$o1->id, $o2->id], $this->idsIn($other, ApplicationStatus::Applied));
        $this->assertSame([$a->id], $this->idsIn($user, ApplicationStatus::Applied));
    }
}
