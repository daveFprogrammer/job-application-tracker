<?php

namespace Database\Factories;

use App\Models\JobApplication;
use App\Models\Reminder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Reminder>
 */
class ReminderFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_application_id' => JobApplication::factory(),
            'remind_at' => fake()->dateTimeBetween('+1 hour', '+2 weeks'),
            'message' => 'Follow up with the recruiter',
        ];
    }

    public function due(): static
    {
        return $this->state(fn () => ['remind_at' => now()->subMinute()]);
    }
}
