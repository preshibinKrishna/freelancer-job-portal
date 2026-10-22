<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

/** @property-read ConversationParticipant|null $pivot */
class Conversation extends Model
{
    protected $guarded = ['id', 'public_id'];

    protected function casts(): array
    {
        return ['last_message_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (Conversation $conversation) => $conversation->public_id ??= (string) Str::ulid());
    }

    /** @return MorphTo<Model, $this> */
    public function context(): MorphTo
    {
        return $this->morphTo();
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsToMany<User, $this, ConversationParticipant> */
    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')->using(ConversationParticipant::class)->withPivot(['last_read_at', 'archived_at'])->withTimestamps();
    }

    /** @return HasMany<ConversationMessage, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class)->oldest();
    }

    /** @return HasMany<ConversationReport, $this> */
    public function reports(): HasMany
    {
        return $this->hasMany(ConversationReport::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
