<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FreelancerReviewStatus;
use App\Models\FreelancerReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FreelancerReview> */
class FreelancerReviewFactory extends Factory
{
    protected $model = FreelancerReview::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['freelancer_project_id' => FreelancerProjectFactory::new(), 'reviewer_id' => UserFactory::new(), 'company_id' => CompanyFactory::new(), 'rating' => fake()->numberBetween(3, 5), 'title' => fake()->sentence(4), 'review' => fake()->paragraph(), 'moderation_status' => FreelancerReviewStatus::Pending];
    }

    public function approved(int $moderatorId): static
    {
        return $this->state(['moderation_status' => FreelancerReviewStatus::Approved, 'moderated_by' => $moderatorId, 'moderated_at' => now()]);
    }
}
