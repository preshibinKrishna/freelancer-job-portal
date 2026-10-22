<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecruitmentPipelineStageChange extends Model
{
    public $timestamps = false;

    protected $fillable = ['pipeline_entry_id', 'actor_id', 'from_stage', 'to_stage', 'note', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<RecruitmentPipelineEntry, $this> */
    public function entry(): BelongsTo
    {
        return $this->belongsTo(RecruitmentPipelineEntry::class, 'pipeline_entry_id');
    }
}
