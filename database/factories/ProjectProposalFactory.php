<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ProjectProposalStatus;
use App\Models\EngagementProject;
use App\Models\ProjectProposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProjectProposal> */
class ProjectProposalFactory extends Factory
{
    protected $model = ProjectProposal::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'engagement_project_id' => EngagementProject::factory(), 'freelancer_id' => User::factory(),
            'cover_letter' => fake()->paragraphs(2, true), 'proposed_price' => fake()->randomFloat(2, 800, 9000),
            'currency' => 'USD', 'proposed_duration_days' => fake()->numberBetween(10, 90),
            'status' => ProjectProposalStatus::Submitted, 'submitted_at' => now(),
        ];
    }
}
