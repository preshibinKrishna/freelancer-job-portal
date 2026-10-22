<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CandidatePipelineStage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** @property CandidatePipelineStage $stage */
class RecruitmentPipelineEntry extends Model
{
    protected $fillable = ['recruitment_request_id', 'candidate_id', 'added_by', 'stage', 'private_notes', 'added_at', 'placed_at'];

    protected function casts(): array
    {
        return ['stage' => CandidatePipelineStage::class, 'added_at' => 'immutable_datetime', 'placed_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (RecruitmentPipelineEntry $entry) => $entry->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<RecruitmentRequest, $this> */
    public function recruitmentRequest(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class);
    }

    /** @return BelongsTo<User, $this> */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }

    /** @return HasMany<RecruitmentInterview, $this> */
    public function interviews(): HasMany
    {
        return $this->hasMany(RecruitmentInterview::class, 'pipeline_entry_id')->orderBy('starts_at');
    }

    /** @return HasMany<RecruitmentPipelineStageChange, $this> */
    public function stageChanges(): HasMany
    {
        return $this->hasMany(RecruitmentPipelineStageChange::class, 'pipeline_entry_id')->orderBy('created_at');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
