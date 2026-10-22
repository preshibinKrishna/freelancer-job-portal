<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Company;
use App\Models\TalentRequirementRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TalentRequirementRequest> */
class TalentRequirementRequestFactory extends Factory
{
    protected $model = TalentRequirementRequest::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'created_by' => UserFactory::new(),
            'category' => 'website',
            'solution_type' => 'ecommerce',
            'details' => ['commerce_model' => 'b2c', 'project_stage' => 'planning'],
            'suggested_skills' => ['E-commerce', 'Payment Integration'],
            'submitted_at' => now(),
        ];
    }
}
