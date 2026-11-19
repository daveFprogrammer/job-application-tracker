<?php

namespace Database\Seeders;

use App\Actions\ResetDemoAccount;
use App\Enums\ApplicationStatus;
use App\Models\Interview;
use App\Models\JobApplication;
use App\Models\Note;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed a local test account with a random board, plus the shared demo account.
     */
    public function run(ResetDemoAccount $demo): void
    {
        $demo->handle();

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $board = [
            ApplicationStatus::Wishlist->value => 3,
            ApplicationStatus::Applied->value => 4,
            ApplicationStatus::Interviewing->value => 2,
            ApplicationStatus::Offer->value => 1,
            ApplicationStatus::Rejected->value => 2,
        ];

        foreach ($board as $status => $count) {
            for ($position = 0; $position < $count; $position++) {
                $application = JobApplication::factory()
                    ->for($user)
                    ->status(ApplicationStatus::from($status))
                    ->create(['position_in_column' => $position]);

                Note::factory()->for($application)->create();

                if ($application->status === ApplicationStatus::Interviewing) {
                    $interview = Interview::factory()->for($application)->create();

                    Reminder::factory()->for($application)->create([
                        'interview_id' => $interview->id,
                        'remind_at' => $interview->scheduled_at->subDay(),
                        'message' => "Interview with {$application->company} tomorrow",
                    ]);
                }
            }
        }
    }
}
