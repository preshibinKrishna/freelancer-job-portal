<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\JobVisibility;
use App\Enums\WorkplaceType;
use App\Models\Job;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Job> */
class JobFactory extends Factory
{
    protected $model = Job::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $title = fake()->jobTitle();

        return [
            'company_id' => CompanyFactory::new(),
            'created_by' => UserFactory::new(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'description' => fake()->paragraphs(3, true),
            'employment_type' => EmploymentType::FullTime,
            'workplace_type' => WorkplaceType::Hybrid,
            'responsibilities' => ['Collaborate with product and engineering teams'],
            'requirements' => ['Relevant professional experience'],
            'benefits' => [],
            'visibility' => JobVisibility::Public,
            'status' => JobStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => JobStatus::Published, 'published_at' => now()]);
    }
}
