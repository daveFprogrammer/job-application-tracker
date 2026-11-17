<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Enums\WorkMode;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'company' => fake()->company(),
            'position' => fake()->randomElement([
                'Backend Developer', 'Full Stack Developer', 'Laravel Developer',
                'PHP Engineer', 'Software Engineer', 'Vue.js Developer',
            ]),
            'job_url' => fake()->url(),
            'location' => fake()->city(),
            'work_mode' => fake()->randomElement(WorkMode::cases()),
            'salary_range' => fake()->optional()->randomElement(['30-35k', '35-42k', '40-50k', '45-55k']),
            'status' => ApplicationStatus::Wishlist,
            'position_in_column' => 0,
            'applied_at' => null,
            'description' => fake()->optional()->paragraph(),
        ];
    }

    public function status(ApplicationStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'applied_at' => $status === ApplicationStatus::Wishlist ? null : fake()->dateTimeBetween('-6 weeks'),
        ]);
    }
}
