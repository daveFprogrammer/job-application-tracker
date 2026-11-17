<?php

namespace Database\Factories;

use App\Enums\InterviewType;
use App\Models\Interview;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Interview>
 */
class InterviewFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'job_application_id' => JobApplication::factory(),
            'scheduled_at' => fake()->dateTimeBetween('+1 day', '+3 weeks'),
            'type' => fake()->randomElement(InterviewType::cases()),
            'location' => fake()->optional()->randomElement(['Google Meet', 'Zoom', 'Microsoft Teams', fake()->address()]),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
