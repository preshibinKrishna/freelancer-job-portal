<?php

declare(strict_types=1);

namespace App\Domain\Admin\Services;

use App\Models\AuditLog;
use App\Models\BillingInvoice;
use App\Models\BillingTransaction;
use App\Models\Company;
use App\Models\ConversationReport;
use App\Models\EngagementProject;
use App\Models\FreelanceContract;
use App\Models\FreelancerProfile;
use App\Models\FreelancerReview;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Location;
use App\Models\Permission;
use App\Models\ProjectCategory;
use App\Models\ProjectProposal;
use App\Models\RecruitmentRequest;
use App\Models\Role;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use App\Models\Verification;

final class AdminSectionCatalog
{
    /** @return array<string, array{label: string, permission: string, model: class-string|null, columns: array<string, string>, relations: list<string>, search: list<string>}> */
    public function sections(): array
    {
        $sections = [
            'dashboard' => ['label' => 'Dashboard', 'permission' => 'admin.dashboard.view', 'model' => null, 'columns' => [], 'relations' => [], 'search' => []],
            'users' => $this->entry('Users', User::class, ['name' => 'Name', 'email' => 'Email', 'status' => 'Status', 'created_at' => 'Joined'], ['roles'], ['name', 'email']),
            'candidates' => $this->entry('Candidates', UserProfile::class, ['user.name' => 'Name', 'headline' => 'Headline', 'primaryLocation.name' => 'Location', 'experience_years' => 'Years', 'availability' => 'Availability'], ['user', 'primaryLocation'], ['headline', 'user.name']),
            'freelancers' => $this->entry('Freelancers', FreelancerProfile::class, ['talentProfile.user.name' => 'Name', 'talentProfile.headline' => 'Headline', 'hourly_rate' => 'Hourly rate', 'currency' => 'Currency', 'verification_status' => 'Verification'], ['talentProfile.user'], ['talentProfile.headline', 'talentProfile.user.name']),
            'employers' => $this->entry('Employers', User::class, ['name' => 'Name', 'email' => 'Email', 'status' => 'Status', 'owned_companies_count' => 'Companies'], [], ['name', 'email'], fn ($query) => $query->whereHas('roles', fn ($roles) => $roles->where('name', 'employer'))->withCount('ownedCompanies')),
            'companies' => $this->entry('Companies', Company::class, ['name' => 'Company', 'owner.name' => 'Owner', 'industry' => 'Industry', 'verification_status' => 'Verification', 'published_at' => 'Published'], ['owner'], ['name', 'industry']),
            'jobs' => $this->entry('Jobs', Job::class, ['title' => 'Job', 'company.name' => 'Company', 'status' => 'Status', 'employment_type' => 'Type', 'created_at' => 'Created'], ['company'], ['title', 'company.name']),
            'projects' => $this->entry('Projects', EngagementProject::class, ['title' => 'Project', 'company.name' => 'Company', 'status' => 'Status', 'budget_min' => 'Budget from', 'currency' => 'Currency'], ['company'], ['title', 'company.name']),
            'applications' => $this->entry('Applications', JobApplication::class, ['job.title' => 'Job', 'candidate.name' => 'Candidate', 'status' => 'Status', 'submitted_at' => 'Submitted'], ['job', 'candidate'], ['job.title', 'candidate.name']),
            'proposals' => $this->entry('Proposals', ProjectProposal::class, ['project.title' => 'Project', 'freelancer.name' => 'Freelancer', 'status' => 'Status', 'proposed_price' => 'Proposed price', 'currency' => 'Currency'], ['project', 'freelancer'], ['project.title', 'freelancer.name']),
            'contracts' => $this->entry('Contracts', FreelanceContract::class, ['project.title' => 'Project', 'client.name' => 'Client', 'freelancer.name' => 'Freelancer', 'status' => 'Status', 'agreed_amount' => 'Amount', 'currency' => 'Currency'], ['project', 'client', 'freelancer'], ['project.title', 'client.name', 'freelancer.name']),
            'recruitment' => $this->entry('Recruitment', RecruitmentRequest::class, ['position' => 'Position', 'company.name' => 'Company', 'status' => 'Status', 'urgency' => 'Urgency', 'submitted_at' => 'Submitted'], ['company'], ['position', 'company.name']),
            'verification' => $this->entry('Verification', Verification::class, ['user.name' => 'User', 'type' => 'Type', 'label' => 'Claim', 'status' => 'Status', 'created_at' => 'Submitted'], ['user'], ['label', 'user.name']),
            'reviews' => $this->entry('Reviews', FreelancerReview::class, ['company.name' => 'Company', 'rating' => 'Rating', 'moderation_status' => 'Status', 'created_at' => 'Created'], ['company'], ['title', 'company.name']),
            'reports' => $this->entry('Reports', ConversationReport::class, ['reporter.name' => 'Reporter', 'reportedUser.name' => 'Reported user', 'reason' => 'Reason', 'created_at' => 'Reported'], ['reporter', 'reportedUser'], ['reason']),
            'payments' => $this->entry('Payments', BillingTransaction::class, ['public_id' => 'Reference', 'type' => 'Type', 'status' => 'Status', 'amount' => 'Amount', 'currency' => 'Currency', 'created_at' => 'Created'], [], ['public_id']),
            'invoices' => $this->entry('Invoices', BillingInvoice::class, ['public_id' => 'Invoice', 'status' => 'Status', 'total' => 'Total', 'currency' => 'Currency', 'issued_at' => 'Issued', 'due_at' => 'Due'], [], ['public_id']),
            'locations' => $this->entry('Locations', Location::class, ['name' => 'Name', 'type' => 'Type', 'parent.name' => 'Parent', 'country_code' => 'Country'], ['parent'], ['name', 'country_code']),
            'it-parks' => $this->entry('IT Parks', Location::class, ['name' => 'Name', 'parent.name' => 'City / Area', 'country_code' => 'Country'], ['parent'], ['name'], fn ($query) => $query->where('type', 'it_park')),
            'skills' => $this->entry('Skills', Skill::class, ['name' => 'Skill', 'slug' => 'Slug', 'created_at' => 'Created'], [], ['name']),
            'categories' => $this->entry('Categories', ProjectCategory::class, ['name' => 'Category', 'slug' => 'Slug', 'sort_order' => 'Order'], [], ['name']),
            'content' => ['label' => 'Content', 'permission' => 'admin.content.view', 'model' => null, 'columns' => [], 'relations' => [], 'search' => []],
            'settings' => $this->entry('Settings', Role::class, ['label' => 'Role', 'name' => 'Role key', 'users_count' => 'Users', 'permissions_count' => 'Permissions'], [], ['name', 'label'], fn ($query) => $query->withCount(['users', 'permissions'])),
            'audit-logs' => $this->entry('Audit Logs', AuditLog::class, ['event' => 'Event', 'actor.name' => 'Actor', 'subject.name' => 'Subject', 'created_at' => 'Time'], ['actor', 'subject'], ['event']),
        ];
        foreach ($sections as $key => &$section) {
            if ($key !== 'dashboard') {
                $section['permission'] = 'admin.section.'.$key.'.view';
            }
        }
        unset($section);

        return $sections;
    }

    /** @param array<string, string> $columns @param list<string> $relations @param list<string> $search @param (\Closure(\Illuminate\Database\Eloquent\Builder): void)|null $scope
     * @return array{label: string, permission: string, model: class-string, columns: array<string, string>, relations: list<string>, search: list<string>, scope?: \Closure}
     */
    private function entry(string $label, string $model, array $columns, array $relations, array $search, ?\Closure $scope = null): array
    {
        $entry = ['label' => $label, 'permission' => '', 'model' => $model, 'columns' => $columns, 'relations' => $relations, 'search' => $search];
        if ($scope !== null) {
            $entry['scope'] = $scope;
        }

        return $entry;
    }
}
