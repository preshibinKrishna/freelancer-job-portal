<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EngagementProjectMilestone extends Model
{
    protected $fillable = ['title', 'description', 'amount', 'duration_days', 'position', 'status', 'completed_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'duration_days' => 'integer', 'position' => 'integer', 'completed_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<EngagementProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(EngagementProject::class, 'engagement_project_id');
    }
}
