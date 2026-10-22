<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CompanyVerificationStatus;
use App\Models\CompanyVerification;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompanyVerification> */
class CompanyVerificationFactory extends Factory
{
    protected $model = CompanyVerification::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'company_id' => CompanyFactory::new(),
            'submitted_by' => UserFactory::new(),
            'status' => CompanyVerificationStatus::Pending,
            'submission_note' => fake()->paragraph(),
        ];
    }
}
