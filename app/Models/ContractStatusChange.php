<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractStatusChange extends Model
{
    public $timestamps = false;

    protected $fillable = ['freelance_contract_id', 'actor_id', 'from_status', 'to_status', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'immutable_datetime'];
    }

    /** @return BelongsTo<FreelanceContract, $this> */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(FreelanceContract::class, 'freelance_contract_id');
    }
}
