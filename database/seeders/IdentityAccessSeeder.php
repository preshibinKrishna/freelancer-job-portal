<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class IdentityAccessSeeder extends Seeder
{
    public function run(): void
    {
        $roles = collect([
            'super_admin' => 'Super administrator',
            'admin' => 'Administrator',
            'recruiter' => 'Recruiter',
            'employer' => 'Employer',
            'candidate' => 'Candidate',
            'freelancer' => 'Freelancer',
        ])->map(fn (string $label, string $name) => Role::query()->firstOrCreate(['name' => $name], ['label' => $label]));

        $permissions = collect([
            'user.view' => 'View user accounts',
            'user.update' => 'Update user accounts',
            'user.roles.assign' => 'Assign user roles',
            'user.suspend' => 'Suspend user accounts',
            'user.delete' => 'Delete user accounts',
            'location.manage' => 'Manage geographic locations',
            'freelancer.verify' => 'Verify freelancer profiles',
            'freelancer.review.moderate' => 'Moderate freelancer reviews',
            'verification.review' => 'Review identity and professional verification requests',
            'admin.dashboard.view' => 'View the administration dashboard',
            'admin.section.users.view' => 'View user administration',
            'admin.section.candidates.view' => 'View candidate administration',
            'admin.section.freelancers.view' => 'View freelancer administration',
            'admin.section.employers.view' => 'View employer administration',
            'admin.section.companies.view' => 'View company administration',
            'admin.section.jobs.view' => 'View job administration',
            'admin.section.projects.view' => 'View project administration',
            'admin.section.applications.view' => 'View application administration',
            'admin.section.proposals.view' => 'View proposal administration',
            'admin.section.contracts.view' => 'View contract administration',
            'admin.section.recruitment.view' => 'View recruitment administration',
            'admin.section.verification.view' => 'View verification administration',
            'admin.section.reviews.view' => 'View review administration',
            'admin.section.reports.view' => 'View report administration',
            'admin.section.payments.view' => 'View payment administration',
            'admin.section.invoices.view' => 'View invoice administration',
            'admin.section.locations.view' => 'View location administration',
            'admin.section.it-parks.view' => 'View IT park administration',
            'admin.section.skills.view' => 'View skill administration',
            'admin.section.categories.view' => 'View category administration',
            'admin.section.content.view' => 'View content administration',
            'admin.section.settings.view' => 'View administration settings',
            'admin.section.audit-logs.view' => 'View audit logs',
        ])->map(fn (string $label, string $name) => Permission::query()->firstOrCreate(['name' => $name], ['label' => $label]));

        $roles['admin']->permissions()->sync($permissions->pluck('id'));
        $roles['super_admin']->permissions()->sync($permissions->pluck('id'));
        $roles['recruiter']->permissions()->sync([]);
        $roles['employer']->permissions()->sync([]);
        $roles['candidate']->permissions()->sync([]);
        $roles['freelancer']->permissions()->sync([]);
    }
}
