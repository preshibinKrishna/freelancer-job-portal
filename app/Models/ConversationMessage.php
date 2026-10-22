<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ConversationMessage extends Model
{
    protected $guarded = ['id', 'public_id'];

    protected static function booted(): void
    {
        static::creating(fn (ConversationMessage $message) => $message->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<Conversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /** @return HasMany<ConversationAttachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(ConversationAttachment::class);
    }
}
