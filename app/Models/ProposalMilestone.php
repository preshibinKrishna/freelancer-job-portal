<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalMilestone extends Model
{
    protected $fillable = ['title', 'description', 'amount', 'duration_days', 'position'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'duration_days' => 'integer', 'position' => 'integer'];
    }

    /** @return BelongsTo<ProjectProposal, $this> */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ProjectProposal::class, 'project_proposal_id');
    }
}
