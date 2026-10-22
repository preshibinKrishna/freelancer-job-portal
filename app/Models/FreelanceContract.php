<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\FreelanceContractStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** @property FreelanceContractStatus $status */
class FreelanceContract extends Model
{
    use HasFactory;

    protected $fillable = ['engagement_project_id', 'project_proposal_id', 'company_id', 'client_id', 'freelancer_id', 'agreed_amount', 'currency', 'start_date', 'end_date', 'terms', 'status', 'activated_at', 'completed_at'];

    protected function casts(): array
    {
        return ['agreed_amount' => 'decimal:2', 'start_date' => 'immutable_date', 'end_date' => 'immutable_date', 'status' => FreelanceContractStatus::class, 'activated_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (FreelanceContract $contract) => $contract->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<EngagementProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(EngagementProject::class, 'engagement_project_id');
    }

    /** @return BelongsTo<ProjectProposal, $this> */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ProjectProposal::class, 'project_proposal_id');
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    /** @return BelongsTo<User, $this> */
    public function freelancer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    /** @return HasMany<ContractMilestone, $this> */
    public function milestones(): HasMany
    {
        return $this->hasMany(ContractMilestone::class)->orderBy('position');
    }

    /** @return HasMany<ContractStatusChange, $this> */
    public function statusChanges(): HasMany
    {
        return $this->hasMany(ContractStatusChange::class)->orderBy('created_at');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
