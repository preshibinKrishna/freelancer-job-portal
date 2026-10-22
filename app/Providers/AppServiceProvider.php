<?php

namespace App\Providers;

use App\Domain\Jobs\Events\JobApplicationStatusChanged;
use App\Domain\Jobs\Events\JobApplicationSubmitted;
use App\Domain\Jobs\Events\JobApplicationWithdrawn;
use App\Domain\Jobs\Listeners\NotifyCandidateOfApplicationStatus;
use App\Domain\Jobs\Listeners\NotifyCompanyOfApplicationWithdrawal;
use App\Domain\Jobs\Listeners\NotifyCompanyOfJobApplication;
use App\Domain\Matching\Contracts\MatchingEngine;
use App\Domain\Matching\Services\WeightedScoringEngine;
use App\Models\Company;
use App\Models\CompanyVerification;
use App\Models\Conversation;
use App\Models\EngagementProject;
use App\Models\EngagementProjectAttachment;
use App\Models\FreelanceContract;
use App\Models\FreelancerProfile;
use App\Models\FreelancerProject;
use App\Models\FreelancerReview;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\Location;
use App\Models\ProfileResume;
use App\Models\ProjectProposal;
use App\Models\RecruitmentInterview;
use App\Models\RecruitmentPipelineEntry;
use App\Models\RecruitmentRequest;
use App\Models\TalentRequirementRequest;
use App\Models\User;
use App\Models\UserProfile;
use App\Policies\CompanyPolicy;
use App\Policies\ConversationPolicy;
use App\Policies\EngagementProjectAttachmentPolicy;
use App\Policies\EngagementProjectPolicy;
use App\Policies\FreelanceContractPolicy;
use App\Policies\FreelancerProfilePolicy;
use App\Policies\FreelancerProjectPolicy;
use App\Policies\FreelancerReviewPolicy;
use App\Policies\JobApplicationPolicy;
use App\Policies\JobPolicy;
use App\Policies\LocationPolicy;
use App\Policies\ProfileResumePolicy;
use App\Policies\ProjectProposalPolicy;
use App\Policies\RecruitmentInterviewPolicy;
use App\Policies\RecruitmentPipelineEntryPolicy;
use App\Policies\RecruitmentRequestPolicy;
use App\Policies\TalentProfilePolicy;
use App\Policies\TalentRequirementRequestPolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(MatchingEngine::class, fn ($app): MatchingEngine => $app->make(config('matching.engine', WeightedScoringEngine::class)));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Location::class, LocationPolicy::class);
        Gate::policy(UserProfile::class, TalentProfilePolicy::class);
        Gate::policy(ProfileResume::class, ProfileResumePolicy::class);
        Gate::policy(RecruitmentRequest::class, RecruitmentRequestPolicy::class);
        Gate::policy(TalentRequirementRequest::class, TalentRequirementRequestPolicy::class);
        Gate::policy(RecruitmentPipelineEntry::class, RecruitmentPipelineEntryPolicy::class);
        Gate::policy(RecruitmentInterview::class, RecruitmentInterviewPolicy::class);
        Gate::policy(FreelancerProfile::class, FreelancerProfilePolicy::class);
        Gate::policy(EngagementProject::class, EngagementProjectPolicy::class);
        Gate::policy(EngagementProjectAttachment::class, EngagementProjectAttachmentPolicy::class);
        Gate::policy(FreelanceContract::class, FreelanceContractPolicy::class);
        Gate::policy(ProjectProposal::class, ProjectProposalPolicy::class);
        Gate::policy(FreelancerProject::class, FreelancerProjectPolicy::class);
        Gate::policy(FreelancerReview::class, FreelancerReviewPolicy::class);
        Gate::policy(Company::class, CompanyPolicy::class);
        Gate::policy(Conversation::class, ConversationPolicy::class);
        Gate::policy(CompanyVerification::class, CompanyPolicy::class);
        Gate::policy(Job::class, JobPolicy::class);
        Gate::policy(JobApplication::class, JobApplicationPolicy::class);
        Gate::define('company.verify', static fn (User $user): bool => $user->hasPermission('company.verify'));
        Gate::define('verification.review', static fn (User $user): bool => $user->isActive() && $user->hasPermission('verification.review'));
        Gate::define('admin.section', static fn (User $user, string $permission): bool => $user->isActive() && $user->hasPermission($permission));
        Event::listen(JobApplicationSubmitted::class, NotifyCompanyOfJobApplication::class);
        Event::listen(JobApplicationStatusChanged::class, NotifyCandidateOfApplicationStatus::class);
        Event::listen(JobApplicationWithdrawn::class, NotifyCompanyOfApplicationWithdrawal::class);
        VerifyEmail::createUrlUsing(fn (User $user): string => URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes((int) config('auth.verification.expire', 60)),
            ['user' => $user->public_id, 'hash' => sha1($user->getEmailForVerification())],
        ));
    }
}
