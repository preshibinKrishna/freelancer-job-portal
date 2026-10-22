<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractMilestoneStatusChange extends Model
{
    public $timestamps = false;

    protected $fillable = ['contract_milestone_id', 'actor_id', 'from_status', 'to_status', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<ContractMilestone, $this> */
    public function milestone(): BelongsTo
    {
        return $this->belongsTo(ContractMilestone::class, 'contract_milestone_id');
    }
}
