<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\FreelancerProjectVerificationStatus;
use App\Models\FreelancerProject;
use App\Models\ProjectCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FreelancerProject> */
class FreelancerProjectFactory extends Factory
{
    protected $model = FreelancerProject::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['freelancer_profile_id' => FreelancerProfileFactory::new(), 'project_category_id' => ProjectCategory::factory(), 'title' => fake()->sentence(4), 'summary' => fake()->paragraph(), 'role' => fake()->jobTitle(), 'project_url' => fake()->url(), 'completed_at' => fake()->dateTimeBetween('-4 years', '-1 month'), 'verification_status' => FreelancerProjectVerificationStatus::Unverified, 'is_featured' => false];
    }

    public function verifiedByCompany(int $companyId, int $userId): static
    {
        return $this->state(['requested_company_id' => $companyId, 'verification_status' => FreelancerProjectVerificationStatus::Verified, 'verified_by' => $userId, 'verified_at' => now()]);
    }
}
