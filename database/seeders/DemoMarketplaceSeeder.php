<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CompanyMemberRole;
use App\Enums\CompanyMembershipStatus;
use App\Enums\CompanySize;
use App\Enums\CompanyVerificationStatus;
use App\Enums\EmploymentType;
use App\Enums\FreelancerProjectVerificationStatus;
use App\Enums\FreelancerReviewStatus;
use App\Enums\FreelancerVerificationStatus;
use App\Enums\JobStatus;
use App\Enums\JobVisibility;
use App\Enums\ProjectPricingType;
use App\Enums\WorkplaceType;
use App\Models\Company;
use App\Models\CompanyMembership;
use App\Models\EngagementProject;
use App\Models\FreelancerProfile;
use App\Models\FreelancerProject;
use App\Models\FreelancerReview;
use App\Models\Job;
use App\Models\Location;
use App\Models\ProjectCategory;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoMarketplaceSeeder extends Seeder
{
    public function run(): void
    {
        $employer = User::query()->where('email', 'employer@example.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@example.test')->firstOrFail();

        DB::transaction(function () use ($employer, $admin): void {
            $locations = Location::query()->whereIn('name', ['Kochi', 'Thiruvananthapuram', 'Kozhikode', 'Infopark', 'Technopark'])
                ->get()->keyBy('name');
            $skills = collect(['Laravel', 'PHP', 'TypeScript', 'React', 'AWS', 'Product Design', 'Python', 'PostgreSQL'])
                ->mapWithKeys(fn (string $name): array => [
                    $name => Skill::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]),
                ]);

            $companies = collect([
                ['name' => 'M Talent Demo Technologies', 'slug' => 'm-talent-demo-technologies', 'industry' => 'Software Development', 'city' => 'Kochi', 'park' => 'Infopark', 'stack' => ['Laravel', 'TypeScript', 'AWS']],
                ['name' => 'Northstar Cloud', 'slug' => 'northstar-cloud', 'industry' => 'Cloud Infrastructure', 'city' => 'Thiruvananthapuram', 'park' => 'Technopark', 'stack' => ['Go', 'Kubernetes', 'AWS']],
                ['name' => 'Morrow Health', 'slug' => 'morrow-health', 'industry' => 'Health Technology', 'city' => 'Kozhikode', 'park' => null, 'stack' => ['PHP', 'React', 'PostgreSQL']],
                ['name' => 'Fieldnote Studio', 'slug' => 'fieldnote-studio', 'industry' => 'Product Design', 'city' => 'Kochi', 'park' => 'Infopark', 'stack' => ['TypeScript', 'Product Design']],
                ['name' => 'Open Current', 'slug' => 'open-current', 'industry' => 'Financial Technology', 'city' => 'Thiruvananthapuram', 'park' => 'Technopark', 'stack' => ['Python', 'PostgreSQL', 'AWS']],
            ])->map(function (array $data) use ($employer, $locations): Company {
                $company = Company::query()->updateOrCreate(['slug' => $data['slug']], [
                    'owner_id' => $employer->id,
                    'name' => $data['name'],
                    'industry' => $data['industry'],
                    'description' => $data['name'].' builds thoughtful digital products with a distributed technology team.',
                    'website' => 'https://example.test/'.$data['slug'],
                    'headquarters_location_id' => $locations->get($data['city'])?->id,
                    'it_park_id' => $data['park'] ? $locations->get($data['park'])?->id : null,
                    'technology_stack' => $data['stack'],
                    'company_size' => CompanySize::Growing,
                    'verification_status' => CompanyVerificationStatus::Verified,
                    'published_at' => now()->subDays(12),
                ]);

                CompanyMembership::query()->updateOrCreate(
                    ['company_id' => $company->id, 'user_id' => $employer->id],
                    ['role' => CompanyMemberRole::Owner, 'status' => CompanyMembershipStatus::Active],
                );

                return $company;
            });

            $jobData = [
                ['company' => 'M Talent Demo Technologies', 'title' => 'Senior Laravel Engineer', 'city' => 'Kochi', 'type' => EmploymentType::FullTime, 'workplace' => WorkplaceType::Hybrid, 'min' => 5, 'max' => 9, 'salaryMin' => 85000, 'salaryMax' => 115000, 'skills' => ['Laravel', 'PHP', 'PostgreSQL']],
                ['company' => 'Northstar Cloud', 'title' => 'Cloud Platform Engineer', 'city' => 'Thiruvananthapuram', 'type' => EmploymentType::FullTime, 'workplace' => WorkplaceType::Remote, 'min' => 4, 'max' => 8, 'salaryMin' => 95000, 'salaryMax' => 135000, 'skills' => ['AWS', 'TypeScript']],
                ['company' => 'Morrow Health', 'title' => 'Product Engineer, Care Systems', 'city' => 'Kozhikode', 'type' => EmploymentType::FullTime, 'workplace' => WorkplaceType::Hybrid, 'min' => 3, 'max' => 7, 'salaryMin' => 72000, 'salaryMax' => 102000, 'skills' => ['PHP', 'React', 'PostgreSQL']],
                ['company' => 'Fieldnote Studio', 'title' => 'Senior Product Designer', 'city' => 'Kochi', 'type' => EmploymentType::Contract, 'workplace' => WorkplaceType::Remote, 'min' => 5, 'max' => 10, 'salaryMin' => 70000, 'salaryMax' => 100000, 'skills' => ['Product Design', 'TypeScript']],
                ['company' => 'Open Current', 'title' => 'Backend Engineer, Payments', 'city' => 'Thiruvananthapuram', 'type' => EmploymentType::FullTime, 'workplace' => WorkplaceType::OnSite, 'min' => 3, 'max' => 6, 'salaryMin' => 78000, 'salaryMax' => 108000, 'skills' => ['Python', 'PostgreSQL', 'AWS']],
                ['company' => 'M Talent Demo Technologies', 'title' => 'Frontend Engineer', 'city' => 'Kochi', 'type' => EmploymentType::FullTime, 'workplace' => WorkplaceType::Hybrid, 'min' => 2, 'max' => 5, 'salaryMin' => 62000, 'salaryMax' => 88000, 'skills' => ['TypeScript', 'React']],
            ];

            foreach ($jobData as $index => $data) {
                $company = $companies->firstWhere('name', $data['company']);
                $slug = Str::slug($data['title']);
                $job = Job::query()->updateOrCreate(['slug' => $slug], [
                    'company_id' => $company->id,
                    'created_by' => $employer->id,
                    'title' => $data['title'],
                    'description' => $data['title'].' role with a product-minded team. You will shape reliable user experiences, work closely with design and platform partners, and help deliver software used every day.',
                    'employment_type' => $data['type'],
                    'workplace_type' => $data['workplace'],
                    'experience_min' => $data['min'],
                    'experience_max' => $data['max'],
                    'salary_min' => $data['salaryMin'],
                    'salary_max' => $data['salaryMax'],
                    'currency' => 'USD',
                    'location_id' => $locations->get($data['city'])?->id,
                    'it_park_id' => $company->it_park_id,
                    'responsibilities' => ['Build and maintain production features', 'Collaborate across engineering, product, and design', 'Improve quality through thoughtful review and testing'],
                    'requirements' => ['Strong experience in the role’s core technologies', 'Clear written communication', 'Care for reliable, accessible products'],
                    'benefits' => ['Flexible working arrangements', 'Learning and development budget', 'Health and wellbeing support'],
                    'application_deadline' => now()->addDays(30 + $index),
                    'visibility' => JobVisibility::Public,
                    'status' => JobStatus::Published,
                    'published_at' => now()->subDays($index + 1),
                ]);
                $job->skills()->sync($skills->only($data['skills'])->pluck('id'));
            }

            $categories = collect(['Product Engineering', 'Cloud & DevOps', 'Product Design', 'Data & AI'])->mapWithKeys(fn (string $name): array => [
                $name => ProjectCategory::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'description' => $name.' projects']),
            ]);
            $freelancerData = [
                ['name' => 'Maya Nair', 'email' => 'maya.nair@demo.example.test', 'headline' => 'Senior Product Engineer · Laravel & APIs', 'city' => 'Kochi', 'rate' => 78, 'project' => 5400, 'skills' => ['Laravel', 'PHP', 'PostgreSQL'], 'category' => 'Product Engineering', 'work' => 'Care coordination platform'],
                ['name' => 'Arjun Menon', 'email' => 'arjun.menon@demo.example.test', 'headline' => 'Cloud Engineer · AWS, platform reliability', 'city' => 'Thiruvananthapuram', 'rate' => 92, 'project' => 7200, 'skills' => ['AWS', 'TypeScript'], 'category' => 'Cloud & DevOps', 'work' => 'Cloud cost observability suite'],
                ['name' => 'Leena Thomas', 'email' => 'leena.thomas@demo.example.test', 'headline' => 'Product Designer · Research to shipped UI', 'city' => 'Kozhikode', 'rate' => 68, 'project' => 4600, 'skills' => ['Product Design', 'TypeScript'], 'category' => 'Product Design', 'work' => 'Patient onboarding redesign'],
                ['name' => 'Dev Shah', 'email' => 'dev.shah@demo.example.test', 'headline' => 'Data Engineer · Python, analytics systems', 'city' => 'Kochi', 'rate' => 84, 'project' => 6100, 'skills' => ['Python', 'PostgreSQL', 'AWS'], 'category' => 'Data & AI', 'work' => 'Real-time risk signals pipeline'],
            ];

            foreach ($freelancerData as $index => $data) {
                $user = User::query()->firstOrCreate(['email' => $data['email']], [
                    'name' => $data['name'], 'password' => Str::random(64), 'email_verified_at' => now(),
                ]);
                $candidateRole = Role::query()->where('name', 'candidate')->firstOrFail();
                $freelancerRole = Role::query()->where('name', 'freelancer')->firstOrFail();
                $user->roles()->syncWithoutDetaching([$candidateRole->id, $freelancerRole->id]);

                $profile = UserProfile::query()->updateOrCreate(['user_id' => $user->id], [
                    'headline' => $data['headline'],
                    'bio' => 'Independent technology professional who partners with product teams to ship dependable, well-crafted digital products.',
                    'location' => $data['city'].', Kerala',
                    'location_id' => $locations->get($data['city'])?->id,
                    'profile_slug' => Str::slug($data['name']),
                    'visibility' => 'public',
                    'availability' => 'available',
                    'experience_years' => 6 + $index,
                    'preferred_work_types' => ['remote', 'contract'],
                ]);
                $profile->skills()->sync($skills->only($data['skills'])->pluck('id'));

                $freelancer = FreelancerProfile::query()->updateOrCreate(['user_profile_id' => $profile->id], [
                    'hourly_rate' => $data['rate'],
                    'project_rate' => $data['project'],
                    'currency' => 'USD',
                    'weekly_capacity' => 20 + ($index * 5),
                    'is_listed' => true,
                    'verification_status' => FreelancerVerificationStatus::Verified,
                    'verified_by' => $admin->id,
                    'verified_at' => now()->subDays($index + 1),
                ]);
                $category = $categories->get($data['category']);
                $freelancer->categories()->syncWithoutDetaching([$category->id]);

                $portfolio = FreelancerProject::query()->firstOrCreate(
                    ['freelancer_profile_id' => $freelancer->id, 'title' => $data['work']],
                    [
                        'project_category_id' => $category->id,
                        'summary' => 'A focused product engagement that improved workflow clarity, performance, and day-to-day usability for a growing technology team.',
                        'role' => $data['headline'],
                        'project_url' => 'https://example.test/work/'.Str::slug($data['work']),
                        'completed_at' => now()->subMonths(4 + $index)->toDateString(),
                        'requested_company_id' => $companies->first()->id,
                        'verification_status' => FreelancerProjectVerificationStatus::Verified,
                        'verified_by' => $admin->id,
                        'verified_at' => now()->subDays($index + 1),
                        'is_visible' => true,
                        'is_featured' => true,
                    ],
                );
                FreelancerReview::query()->firstOrCreate(
                    ['freelancer_project_id' => $portfolio->id],
                    [
                        'reviewer_id' => $employer->id,
                        'company_id' => $companies->first()->id,
                        'rating' => 5,
                        'title' => 'A thoughtful, dependable partner',
                        'review' => 'Communicated clearly, delivered carefully, and made the team’s product stronger.',
                        'moderation_status' => FreelancerReviewStatus::Approved,
                        'moderated_by' => $admin->id,
                        'moderated_at' => now(),
                    ],
                );
            }

            $projectData = [
                ['company' => 'M Talent Demo Technologies', 'title' => 'Build an accessible analytics workspace', 'categorySkills' => ['Laravel', 'TypeScript', 'React'], 'budget' => [4500, 7000]],
                ['company' => 'Northstar Cloud', 'title' => 'Modernize cloud deployment pipelines', 'categorySkills' => ['AWS', 'TypeScript'], 'budget' => [6000, 9500]],
                ['company' => 'Morrow Health', 'title' => 'Design a patient onboarding experience', 'categorySkills' => ['Product Design', 'React'], 'budget' => [3500, 6000]],
                ['company' => 'Open Current', 'title' => 'Create a secure transaction reporting service', 'categorySkills' => ['Python', 'PostgreSQL', 'AWS'], 'budget' => [7000, 11000]],
            ];

            foreach ($projectData as $index => $data) {
                $company = $companies->firstWhere('name', $data['company']);
                $project = EngagementProject::query()->updateOrCreate(['company_id' => $company->id, 'title' => $data['title']], [
                    'created_by' => $employer->id,
                    'description' => 'Partner with '.$company->name.' to deliver a well-scoped product initiative. The work includes discovery, implementation, documentation, and a collaborative handover with the internal team.',
                    'budget_min' => $data['budget'][0],
                    'budget_max' => $data['budget'][1],
                    'currency' => 'USD',
                    'pricing_type' => ProjectPricingType::Fixed,
                    'estimated_duration_days' => 30 + ($index * 7),
                    'experience_min' => 3,
                    'location_id' => $company->headquarters_location_id,
                    'is_remote' => true,
                    'application_deadline' => now()->addWeeks(3 + $index),
                    'status' => 'published',
                    'published_at' => now()->subDays($index + 1),
                ]);
                $project->skills()->sync($skills->only($data['categorySkills'])->pluck('id'));
            }
        });
    }
}
