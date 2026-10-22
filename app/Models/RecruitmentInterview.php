<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RecruitmentInterviewStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * @property RecruitmentInterviewStatus $status
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 */
class RecruitmentInterview extends Model
{
    protected $fillable = ['pipeline_entry_id', 'scheduled_by', 'title', 'starts_at', 'ends_at', 'location_or_link', 'private_notes', 'status'];

    protected function casts(): array
    {
        return ['status' => RecruitmentInterviewStatus::class, 'starts_at' => 'immutable_datetime', 'ends_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (RecruitmentInterview $interview) => $interview->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<RecruitmentPipelineEntry, $this> */
    public function pipelineEntry(): BelongsTo
    {
        return $this->belongsTo(RecruitmentPipelineEntry::class, 'pipeline_entry_id');
    }

    /** @return BelongsTo<User, $this> */
    public function scheduler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scheduled_by');
    }
}
