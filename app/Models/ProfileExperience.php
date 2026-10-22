<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProfileExperienceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $title
 * @property string $company
 * @property bool $is_current
 * @property Carbon|null $started_at
 * @property Carbon|null $ended_at
 */
class ProfileExperience extends Model
{
    /** @use HasFactory<ProfileExperienceFactory> */
    use HasFactory;

    protected $fillable = ['title', 'company', 'location', 'description', 'started_at', 'ended_at', 'is_current'];

    protected function casts(): array
    {
        return ['started_at' => 'date', 'ended_at' => 'date', 'is_current' => 'boolean'];
    }

    /** @return BelongsTo<UserProfile, $this> */
    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class);
    }
}
