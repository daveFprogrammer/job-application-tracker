<?php

namespace App\Actions;

use App\Enums\ApplicationStatus;
use App\Enums\InterviewType;
use App\Enums\WorkMode;
use App\Models\JobApplication;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Creates the shared demo account if needed and rebuilds its board from
 * scratch. The data is hand-written (no Faker: it is a dev dependency and
 * is not installed in production) with dates relative to "now", so the
 * board always looks fresh: upcoming interviews, recent notes, etc.
 */
class ResetDemoAccount
{
    public const LAST_RESET_CACHE_KEY = 'demo:last_reset_at';

    public function handle(): User
    {
        $user = DB::transaction(function (): User {
            $user = $this->demoUser();

            // Interviews, notes and reminders cascade on delete.
            $user->jobApplications()->delete();

            $this->seedBoard($user);

            return $user;
        });

        Cache::forever(self::LAST_RESET_CACHE_KEY, now()->toIso8601String());

        return $user;
    }

    public function isStale(): bool
    {
        $lastReset = Cache::get(self::LAST_RESET_CACHE_KEY);

        if (! is_string($lastReset) || ! User::query()->where('is_demo', true)->exists()) {
            return true;
        }

        return CarbonImmutable::parse($lastReset)
            ->addHours((int) config('demo.reset_after_hours'))
            ->isPast();
    }

    private function demoUser(): User
    {
        $user = User::query()->where('is_demo', true)->first() ?? new User;

        if (! $user->exists) {
            $user->email = $this->freeDemoEmail();
        }

        $user->forceFill([
            'name' => 'Demo User',
            'is_demo' => true,
            'email_verified_at' => now(),
            // Random and never shown: the only way in is the demo login button.
            'password' => Str::password(64),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return $user;
    }

    /**
     * Someone may have registered the configured address before the demo
     * account existed: the demo then gets a unique address of its own.
     */
    private function freeDemoEmail(): string
    {
        $email = (string) config('demo.email');

        if (! User::query()->where('email', $email)->exists()) {
            return $email;
        }

        return 'demo-'.Str::lower(Str::random(10)).'@job-application-tracker.test';
    }

    private function seedBoard(User $user): void
    {
        $now = CarbonImmutable::now();

        $board = [
            ApplicationStatus::Wishlist->value => [
                [
                    'company' => 'Nebula Labs',
                    'position' => 'Senior Laravel Developer',
                    'location' => 'Milano',
                    'work_mode' => WorkMode::Hybrid,
                    'salary_range' => '45-52k',
                    'description' => "Product team building a B2B SaaS on Laravel + Vue.\nAsk a former colleague for a referral first.",
                ],
                [
                    'company' => 'Verdecloud',
                    'position' => 'Backend Engineer (PHP)',
                    'location' => 'Remote (EU)',
                    'work_mode' => WorkMode::Remote,
                    'salary_range' => '40-48k',
                    'description' => 'Green-hosting startup, small team, async culture.',
                ],
                [
                    'company' => 'Pixelforge',
                    'position' => 'Full Stack Developer',
                    'location' => 'Bologna',
                    'work_mode' => WorkMode::Onsite,
                ],
            ],
            ApplicationStatus::Applied->value => [
                [
                    'company' => 'Arcadia Payments',
                    'position' => 'PHP Developer',
                    'location' => 'Torino',
                    'work_mode' => WorkMode::Hybrid,
                    'salary_range' => '38-44k',
                    'applied_at' => $now->subDays(3),
                    'notes' => ['Applied through the careers page, cover letter tailored to the payments domain.'],
                    'reminder' => [$now->addDays(4)->setTime(9, 0), 'Follow up with the recruiter if there is no answer'],
                ],
                [
                    'company' => 'Lumen Health',
                    'position' => 'Backend Developer',
                    'location' => 'Remote (Italy)',
                    'work_mode' => WorkMode::Remote,
                    'applied_at' => $now->subDays(8),
                    'notes' => ['Referral from Marco. Stack: Laravel, PostgreSQL, AWS.'],
                ],
                [
                    'company' => 'Orbit Logistics',
                    'position' => 'Software Engineer',
                    'location' => 'Verona',
                    'work_mode' => WorkMode::Onsite,
                    'salary_range' => '35-40k',
                    'applied_at' => $now->subDays(12),
                ],
            ],
            ApplicationStatus::Interviewing->value => [
                [
                    'company' => 'Kite Studio',
                    'position' => 'Laravel Developer',
                    'location' => 'Milano',
                    'work_mode' => WorkMode::Hybrid,
                    'salary_range' => '42-48k',
                    'applied_at' => $now->subDays(15),
                    'notes' => [
                        'HR call went well: team of 6, two-week sprints, code review on every PR.',
                        'Prepare: queues and jobs, testing strategy, a project to walk through.',
                    ],
                    'interviews' => [
                        [$now->subDays(5)->setTime(11, 0), InterviewType::Phone, 'Phone', 'Screening with the HR team.'],
                        [$now->addDays(2)->setTime(15, 0), InterviewType::Technical, 'Google Meet', 'Live coding + architecture discussion with the tech lead.'],
                    ],
                ],
                [
                    'company' => 'Brightwave',
                    'position' => 'Backend Engineer',
                    'location' => 'Remote (EU)',
                    'work_mode' => WorkMode::Remote,
                    'applied_at' => $now->subDays(10),
                    'notes' => ['Take-home exercise submitted: REST API with Laravel and tests.'],
                    'interviews' => [
                        [$now->addDays(5)->setTime(10, 30), InterviewType::Video, 'Zoom', 'Review of the take-home with two engineers.'],
                    ],
                ],
            ],
            ApplicationStatus::Offer->value => [
                [
                    'company' => 'Quadrant Software',
                    'position' => 'Senior PHP Developer',
                    'location' => 'Padova',
                    'work_mode' => WorkMode::Hybrid,
                    'salary_range' => '50k + bonus',
                    'applied_at' => $now->subDays(30),
                    'notes' => [
                        'Offer received: 50k, 2 days remote per week. Answer by the end of next week.',
                        'Negotiate the training budget.',
                    ],
                    'interviews' => [
                        [$now->subDays(14)->setTime(14, 0), InterviewType::Onsite, 'Via Roma 12, Padova', 'Final round with the CTO.'],
                    ],
                    'reminder' => [$now->addDays(6)->setTime(18, 0), 'Reply to the Quadrant offer'],
                ],
            ],
            ApplicationStatus::Rejected->value => [
                [
                    'company' => 'Monolith Corp',
                    'position' => 'PHP Engineer',
                    'location' => 'Roma',
                    'work_mode' => WorkMode::Onsite,
                    'applied_at' => $now->subDays(25),
                    'notes' => ['Feedback: looking for more experience with Kubernetes. Worth studying.'],
                ],
                [
                    'company' => 'Sprout Media',
                    'position' => 'Web Developer',
                    'location' => 'Firenze',
                    'work_mode' => WorkMode::Hybrid,
                    'applied_at' => $now->subDays(40),
                ],
            ],
        ];

        foreach ($board as $status => $applications) {
            foreach ($applications as $position => $data) {
                /** @var JobApplication $application */
                $application = $user->jobApplications()->create([
                    'company' => $data['company'],
                    'position' => $data['position'],
                    'location' => $data['location'],
                    'work_mode' => $data['work_mode'],
                    'salary_range' => $data['salary_range'] ?? null,
                    'description' => $data['description'] ?? null,
                    'applied_at' => $data['applied_at'] ?? null,
                    'status' => $status,
                    'position_in_column' => $position,
                ]);

                foreach ($data['notes'] ?? [] as $body) {
                    $application->notes()->create(['body' => $body]);
                }

                foreach ($data['interviews'] ?? [] as [$scheduledAt, $type, $location, $notes]) {
                    $interview = $application->interviews()->create([
                        'scheduled_at' => $scheduledAt,
                        'type' => $type,
                        'location' => $location,
                        'notes' => $notes,
                    ]);

                    if ($scheduledAt->isFuture()) {
                        $application->reminders()->create([
                            'interview_id' => $interview->id,
                            'remind_at' => $scheduledAt->subDay(),
                            'message' => "Interview with {$application->company} tomorrow",
                        ]);
                    }
                }

                if (isset($data['reminder'])) {
                    [$remindAt, $message] = $data['reminder'];
                    $application->reminders()->create(['remind_at' => $remindAt, 'message' => $message]);
                }
            }
        }
    }
}
