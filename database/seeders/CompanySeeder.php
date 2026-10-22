<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::query()->firstOrCreate(
            ['name' => 'company.verify'],
            ['label' => 'Review company verification'],
        );

        foreach (['admin', 'super_admin'] as $name) {
            Role::query()->where('name', $name)->firstOrFail()->permissions()->syncWithoutDetaching([$permission->id]);
        }
    }
}
