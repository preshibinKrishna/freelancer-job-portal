<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ProjectCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProjectCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Web & Software Development', 'Mobile Applications', 'Cloud & DevOps', 'Data & Analytics',
            'AI & Machine Learning', 'Cybersecurity', 'Product & UX Design', 'Quality Engineering',
            'Blockchain & Web3', 'IT Consulting',
        ];

        foreach ($categories as $index => $name) {
            ProjectCategory::query()->updateOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'sort_order' => $index + 1]);
        }
    }
}
