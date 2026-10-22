<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProjectCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ProjectCategory> */
class ProjectCategoryFactory extends Factory
{
    protected $model = ProjectCategory::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['name' => $name, 'slug' => Str::slug($name), 'description' => fake()->sentence(), 'sort_order' => 0];
    }
}
