<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EngagementProjectAttachment extends Model
{
    protected $fillable = ['disk', 'path', 'original_filename', 'mime_type', 'size_bytes'];

    protected $hidden = ['disk', 'path'];

    protected static function booted(): void
    {
        static::creating(fn (EngagementProjectAttachment $attachment) => $attachment->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<EngagementProject, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(EngagementProject::class, 'engagement_project_id');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
