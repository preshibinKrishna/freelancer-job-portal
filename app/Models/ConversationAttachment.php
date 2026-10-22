<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ConversationAttachment extends Model
{
    protected $guarded = ['id', 'public_id'];

    protected static function booted(): void
    {
        static::creating(fn (ConversationAttachment $attachment) => $attachment->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<ConversationMessage, $this> */
    public function message(): BelongsTo
    {
        return $this->belongsTo(ConversationMessage::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
