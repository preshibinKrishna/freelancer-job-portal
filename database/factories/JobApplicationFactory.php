<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\JobApplicationStatus;
use App\Models\JobApplication;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<JobApplication> */
class JobApplicationFactory extends Factory
{
    protected $model = JobApplication::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'job_id' => JobFactory::new()->published(),
            'candidate_id' => UserFactory::new(),
            'cover_letter' => fake()->paragraph(),
            'status' => JobApplicationStatus::Applied,
            'submitted_at' => now(),
        ];
    }
}
