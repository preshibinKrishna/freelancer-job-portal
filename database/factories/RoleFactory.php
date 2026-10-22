<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Role> */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $name = fake()->unique()->slug(2, '_');

        return ['name' => $name, 'label' => str($name)->replace('_', ' ')->title()->toString()];
    }
}
