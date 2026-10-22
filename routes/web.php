<?php

use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\UserIdentityController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyUserEmailController;
use App\Http\Controllers\CompanyProfileController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FreelancerProfileController;
use App\Http\Controllers\JobDetailController;
use App\Http\Controllers\LocationPageController;
use App\Http\Controllers\MarketingHomeController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\ProfileResumeController;
use App\Http\Controllers\ProjectAttachmentController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\SkillPageController;
use App\Http\Controllers\TalentProfileController;
use App\Http\Middleware\MarkPrivateResponsesNoindex;
use App\Livewire\Admin\AdminConsole;
use App\Livewire\Admin\ReviewFreelancerReviews;
use App\Livewire\Admin\ReviewFreelancerVerificationRequests;
use App\Livewire\Admin\ReviewVerifications;
use App\Livewire\Companies\CompanyDashboard;
use App\Livewire\Companies\ManageCompany;
use App\Livewire\Companies\ReviewCompanyVerifications;
use App\Livewire\Contracts\ContractWorkspace;
use App\Livewire\Freelancers\CompanyProjectVerifications;
use App\Livewire\Freelancers\ManageFreelancerProfile;
use App\Livewire\Freelancers\SearchFreelancers;
use App\Livewire\Jobs\CandidateApplications;
use App\Livewire\Jobs\JobDashboard;
use App\Livewire\Jobs\ManageApplications;
use App\Livewire\Jobs\ManageJob;
use App\Livewire\Jobs\SavedJobs;
use App\Livewire\Jobs\SearchJobs;
use App\Livewire\Messaging\ConversationInbox;
use App\Livewire\Profiles\ManageTalentProfile;
use App\Livewire\Projects\BrowseProjects;
use App\Livewire\Projects\ManageEngagementProject;
use App\Livewire\Projects\ProjectWorkspace;
use App\Livewire\Recruitment\EmployerRecruitmentRequests;
use App\Livewire\Recruitment\ManageRecruitmentRequest;
use App\Livewire\Recruitment\ManageTalentRequirement;
use App\Livewire\Recruitment\RecruiterDashboard;
use App\Livewire\Recruitment\RecruitmentRequestWorkspace;
use App\Livewire\Verifications\ManageVerifications;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\Route;

Route::get('/', MarketingHomeController::class)->name('home');

Route::post('/webhooks/payments/{provider}', PaymentWebhookController::class)
    ->where('provider', '[a-z0-9_-]{2,60}')
    ->middleware('throttle:120,1')
    ->withoutMiddleware(ValidateCsrfToken::class)
    ->name('webhooks.payments');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('throttle:3,1')->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->middleware('throttle:6,1')->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('throttle:6,1')->name('password.update');
});

Route::middleware(['auth', 'account.active', MarkPrivateResponsesNoindex::class])->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', EmailVerificationPromptController::class)->name('verification.notice');
    Route::get('/email/verify/{user}/{hash}', VerifyUserEmailController::class)->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])->middleware('throttle:6,1')->name('verification.send');

    Route::middleware('verified')->group(function (): void {
        Route::get('/messages', ConversationInbox::class)->name('messages.index');
        Route::get('/messages/{conversation:public_id}', ConversationInbox::class)->name('messages.show');
        Route::get('/messages/start/{type}/{publicId}', [ConversationController::class, 'start'])->whereIn('type', ['application', 'proposal', 'recruitment-candidate', 'recruitment'])->name('messages.start');
        Route::get('/messages/attachments/{attachment}', [ConversationController::class, 'download'])->name('messages.attachments.download');
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::put('/admin/users/{user}/roles', [UserIdentityController::class, 'assignRole'])->name('admin.users.roles.assign');
        Route::delete('/admin/users/{user}/roles/{role}', [UserIdentityController::class, 'removeRole'])->name('admin.users.roles.remove');
        Route::put('/admin/users/{user}/suspend', [UserIdentityController::class, 'suspend'])->name('admin.users.suspend');
        Route::put('/admin/users/{user}/activate', [UserIdentityController::class, 'activate'])->name('admin.users.activate');
        Route::get('/admin/locations', [LocationController::class, 'index'])->name('admin.locations.index');
        Route::get('/admin/locations/create', [LocationController::class, 'create'])->name('admin.locations.create');
        Route::post('/admin/locations', [LocationController::class, 'store'])->name('admin.locations.store');
        Route::get('/admin/locations/{location}/edit', [LocationController::class, 'edit'])->name('admin.locations.edit');
        Route::put('/admin/locations/{location}', [LocationController::class, 'update'])->name('admin.locations.update');
        Route::delete('/admin/locations/{location}', [LocationController::class, 'destroy'])->name('admin.locations.destroy');
        Route::get('/employer/companies', CompanyDashboard::class)->name('employer.companies.index');
        Route::get('/employer/companies/create', ManageCompany::class)->name('employer.companies.create');
        Route::get('/employer/companies/{company}/edit', ManageCompany::class)->name('employer.companies.edit');
        Route::get('/admin/company-verifications', ReviewCompanyVerifications::class)->name('admin.company-verifications.index');
        Route::get('/employer/jobs', JobDashboard::class)->name('employer.jobs.index');
        Route::get('/employer/jobs/create', ManageJob::class)->name('employer.jobs.create');
        Route::get('/employer/jobs/{job}/edit', ManageJob::class)->name('employer.jobs.edit');
        Route::get('/employer/jobs/{job}/applications', ManageApplications::class)->name('employer.jobs.applications');
        Route::get('/employer/projects/create', ManageEngagementProject::class)->name('employer.projects.create');
        Route::get('/employer/projects/{project:public_id}/edit', ManageEngagementProject::class)->name('employer.projects.edit');
        Route::get('/employer/recruitment-requests', EmployerRecruitmentRequests::class)->name('employer.recruitment-requests.index');
        Route::get('/employer/recruitment-requests/create', ManageRecruitmentRequest::class)->name('employer.recruitment-requests.create');
        Route::get('/employer/talent-requirements/create', ManageTalentRequirement::class)->name('employer.talent-requirements.create');
        Route::get('/employer/talent-requirements/{request:public_id}/matches', ManageTalentRequirement::class)->name('employer.talent-requirements.matches');
        Route::get('/recruitment/requests/{recruitmentRequest:public_id}', RecruitmentRequestWorkspace::class)->name('recruitment.requests.show');
        Route::get('/recruiter/dashboard', RecruiterDashboard::class)->name('recruiter.dashboard');
        Route::get('/contracts/{contract:public_id}', ContractWorkspace::class)->name('contracts.show');
        Route::get('/projects/attachments/{attachment:public_id}', [ProjectAttachmentController::class, 'download'])->name('projects.attachments.download');
        Route::get('/candidate/applications', CandidateApplications::class)->name('candidate.applications.index');
        Route::get('/candidate/saved-jobs', SavedJobs::class)->name('candidate.saved-jobs.index');
        Route::get('/candidate/profile', ManageTalentProfile::class)->name('candidate.profile.edit');
        Route::get('/candidate/resumes/{resume}/download', [ProfileResumeController::class, 'download'])->name('candidate.resumes.download');
        Route::get('/freelancer/profile', ManageFreelancerProfile::class)->name('freelancer.profile.edit');
        Route::get('/employer/freelancer-project-verifications', CompanyProjectVerifications::class)->name('employer.freelancer-project-verifications.index');
        Route::get('/admin/freelancer-reviews', ReviewFreelancerReviews::class)->name('admin.freelancer-reviews.index');
        Route::get('/admin/freelancer-verifications', ReviewFreelancerVerificationRequests::class)->name('admin.freelancer-verifications.index');
        Route::get('/admin/verifications', ReviewVerifications::class)->name('admin.verifications.index');
        Route::get('/admin/{section?}', AdminConsole::class)->name('admin.console');
        Route::get('/account/verifications', ManageVerifications::class)->name('verifications.index');
    });
});

if (app()->environment(['local', 'testing'])) {
    Route::view('/_design-system', 'design-system.showcase')->name('design-system.showcase');
}

Route::get('/locations/{path}', LocationPageController::class)->where('path', '.*')->name('locations.show');
Route::get('/robots.txt', RobotsController::class)->name('robots');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/sitemaps/{part}.xml', [SitemapController::class, 'part'])->whereNumber('part')->name('sitemap.part');
Route::get('/skills/{skill:slug}', SkillPageController::class)->name('skills.show');
Route::get('/jobs', SearchJobs::class)->name('jobs.index');
Route::get('/projects', BrowseProjects::class)->name('projects.index');
Route::get('/projects/{project:public_id}', ProjectWorkspace::class)->name('projects.show');
Route::get('/jobs/{job:slug}', [JobDetailController::class, 'show'])->name('jobs.show');
Route::get('/companies/{company:slug}', [CompanyProfileController::class, 'show'])->name('companies.show');
Route::get('/freelancers', SearchFreelancers::class)->name('freelancers.index');
Route::get('/freelancers/{slug}', [FreelancerProfileController::class, 'show'])->name('freelancers.show');
Route::get('/talent/{slug}', [TalentProfileController::class, 'show'])->name('talent.show');
Route::get('/talent/{user:public_id}/photo', [ProfilePhotoController::class, 'show'])->name('talent.photo');
