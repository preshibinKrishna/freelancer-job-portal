<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CompanyMemberRole;
use App\Enums\CompanyMembershipStatus;
use App\Models\CompanyMembership;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CompanyMembership> */
class CompanyMembershipFactory extends Factory
{
    protected $model = CompanyMembership::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'company_id' => CompanyFactory::new(),
            'user_id' => UserFactory::new(),
            'role' => CompanyMemberRole::Member,
            'status' => CompanyMembershipStatus::Active,
        ];
    }
}
