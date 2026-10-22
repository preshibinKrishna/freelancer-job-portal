<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EngagementProjectStatus;
use App\Enums\ProjectPricingType;
use App\Models\Company;
use App\Models\EngagementProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EngagementProject> */
class EngagementProjectFactory extends Factory
{
    protected $model = EngagementProject::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(), 'created_by' => User::factory(), 'title' => fake()->sentence(5),
            'description' => fake()->paragraphs(3, true), 'budget_min' => 3000, 'budget_max' => 12000,
            'currency' => 'USD', 'pricing_type' => ProjectPricingType::Fixed, 'estimated_duration_days' => 45,
            'experience_min' => 3, 'is_remote' => true, 'application_deadline' => now()->addWeeks(3),
            'status' => EngagementProjectStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(['status' => EngagementProjectStatus::Published, 'published_at' => now()]);
    }
}
