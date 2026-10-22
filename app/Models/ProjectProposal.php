<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProjectProposalStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** @property ProjectProposalStatus $status */
class ProjectProposal extends Model
{
    use HasFactory;

    protected $fillable = ['engagement_project_id', 'freelancer_id', 'cover_letter', 'proposed_price', 'currency', 'proposed_duration_days', 'status', 'submitted_at', 'withdrawn_at'];

    protected function casts(): array
    {
        return ['proposed_price' => 'decimal:2', 'proposed_duration_days' => 'integer', 'status' => ProjectProposalStatus::class, 'submitted_at' => 'immutable_datetime', 'withdrawn_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (ProjectProposal $proposal) => $proposal->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<EngagementProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(EngagementProject::class, 'engagement_project_id');
    }

    /** @return BelongsTo<EngagementProject, $this> */
    public function engagementProject(): BelongsTo
    {
        return $this->project();
    }

    /** @return BelongsTo<User, $this> */
    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    /** @return HasMany<ProposalMilestone, $this> */
    public function milestones(): HasMany
    {
        return $this->hasMany(ProposalMilestone::class);
    }

    /** @return BelongsToMany<FreelancerProject, $this> */
    public function portfolioItems(): BelongsToMany
    {
        return $this->belongsToMany(FreelancerProject::class, 'proposal_portfolio_items');
    }

    /** @return HasMany<ProjectMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ProjectMessage::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
