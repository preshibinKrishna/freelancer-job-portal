<?php

namespace App\Models;

use App\Enums\AccountStatus;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

/**
 * @property string $public_id
 * @property AccountStatus $status
 * @property int $session_version
 */
#[Fillable(['name', 'email', 'password', 'status', 'timezone', 'locale'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            $user->public_id ??= (string) Str::ulid();
            $user->session_version ??= 0;
        });
    }

    protected function casts(): array
    {
        return [
            'status' => AccountStatus::class,
            'session_version' => 'integer',
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<Role, $this> */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)->withPivot(['assigned_by'])->withTimestamps();
    }

    /** @return HasOne<UserProfile, $this> */
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    /** @return HasMany<UserIdentity, $this> */
    public function identities(): HasMany
    {
        return $this->hasMany(UserIdentity::class);
    }

    /** @return HasMany<Verification, $this> */
    public function verifications(): HasMany
    {
        return $this->hasMany(Verification::class);
    }

    /** @return HasMany<Company, $this> */
    public function ownedCompanies(): HasMany
    {
        return $this->hasMany(Company::class, 'owner_id');
    }

    /** @return BelongsToMany<Company, $this> */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'company_memberships')
            ->withPivot(['role', 'status', 'invited_by'])
            ->withTimestamps()
            ->wherePivot('status', 'active');
    }

    /** @return HasMany<CompanyMembership, $this> */
    public function companyMemberships(): HasMany
    {
        return $this->hasMany(CompanyMembership::class);
    }

    /** @return HasMany<Job, $this> */
    public function createdJobs(): HasMany
    {
        return $this->hasMany(Job::class, 'created_by');
    }

    /** @return HasMany<JobApplication, $this> */
    public function jobApplications(): HasMany
    {
        return $this->hasMany(JobApplication::class, 'candidate_id');
    }

    /** @return BelongsToMany<Job, $this> */
    public function savedJobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'saved_jobs')->withTimestamps();
    }

    /** @return BelongsToMany<EngagementProject, $this> */
    public function savedEngagementProjects(): BelongsToMany
    {
        return $this->belongsToMany(EngagementProject::class, 'saved_engagement_projects')->withTimestamps();
    }

    /** @return BelongsToMany<Conversation, $this, ConversationParticipant> */
    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')->using(ConversationParticipant::class)->withPivot(['last_read_at', 'archived_at'])->withTimestamps();
    }

    /** @return MorphMany<BillingSubscription, $this> */
    public function billingSubscriptions(): MorphMany
    {
        return $this->morphMany(BillingSubscription::class, 'subscriber');
    }

    /** @return MorphMany<BillingInvoice, $this> */
    public function billingInvoices(): MorphMany
    {
        return $this->morphMany(BillingInvoice::class, 'bill_to');
    }

    /** @return BelongsToMany<User, $this> */
    public function blocks(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blocker_id', 'blocked_id')->withTimestamps();
    }

    /** @return BelongsToMany<User, $this> */
    public function blockedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_blocks', 'blocked_id', 'blocker_id')->withTimestamps();
    }

    /** @return HasMany<ProjectProposal, $this> */
    public function projectProposals(): HasMany
    {
        return $this->hasMany(ProjectProposal::class, 'freelancer_id');
    }

    /** @return HasMany<FreelanceContract, $this> */
    public function freelanceContractsAsClient(): HasMany
    {
        return $this->hasMany(FreelanceContract::class, 'client_id');
    }

    /** @return HasMany<FreelanceContract, $this> */
    public function freelanceContractsAsFreelancer(): HasMany
    {
        return $this->hasMany(FreelanceContract::class, 'freelancer_id');
    }

    /** @return HasMany<RecruitmentRequest, $this> */
    public function createdRecruitmentRequests(): HasMany
    {
        return $this->hasMany(RecruitmentRequest::class, 'created_by');
    }

    /** @return HasMany<RecruitmentRequest, $this> */
    public function assignedRecruitmentRequests(): HasMany
    {
        return $this->hasMany(RecruitmentRequest::class, 'assigned_recruiter_id');
    }

    /** @return HasMany<RecruitmentPipelineEntry, $this> */
    public function recruitmentPipelineEntries(): HasMany
    {
        return $this->hasMany(RecruitmentPipelineEntry::class, 'candidate_id');
    }

    public function hasRole(string ...$roles): bool
    {
        return $this->roles()->whereIn('name', $roles)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->roles()->whereHas('permissions', fn ($query) => $query->where('name', $permission))->exists();
    }

    public function isActive(): bool
    {
        return $this->status === AccountStatus::Active;
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
