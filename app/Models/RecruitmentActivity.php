<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentActivity extends Model
{
    public $timestamps = false;

    protected $fillable = ['recruitment_request_id', 'pipeline_entry_id', 'actor_id', 'activity_type', 'metadata', 'created_at'];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<RecruitmentRequest, $this> */
    public function recruitmentRequest(): BelongsTo
    {
        return $this->belongsTo(RecruitmentRequest::class);
    }

    /** @return BelongsTo<RecruitmentPipelineEntry, $this> */
    public function pipelineEntry(): BelongsTo
    {
        return $this->belongsTo(RecruitmentPipelineEntry::class, 'pipeline_entry_id');
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
