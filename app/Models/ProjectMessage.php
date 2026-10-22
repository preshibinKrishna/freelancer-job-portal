<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ProjectMessage extends Model
{
    protected $fillable = ['author_id', 'body'];

    protected static function booted(): void
    {
        static::creating(fn (ProjectMessage $message) => $message->public_id ??= (string) Str::ulid());
    }

    /** @return BelongsTo<ProjectProposal, $this> */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(ProjectProposal::class, 'project_proposal_id');
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
