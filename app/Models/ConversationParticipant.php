<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * @property-read Carbon|null $last_read_at
 * @property-read Carbon|null $archived_at
 */
class ConversationParticipant extends Pivot
{
    public $incrementing = true;

    protected function casts(): array
    {
        return ['last_read_at' => 'immutable_datetime', 'archived_at' => 'immutable_datetime'];
    }
}
