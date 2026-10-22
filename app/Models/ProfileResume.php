<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProfileResumeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** @property string $public_id @property string $disk @property string $path @property string $original_filename @property string $scan_status @property-read UserProfile|null $userProfile */
class ProfileResume extends Model
{
    /** @use HasFactory<ProfileResumeFactory> */
    use HasFactory;

    protected $fillable = ['disk', 'path', 'original_filename', 'mime_type', 'size_bytes', 'scan_status', 'uploaded_at'];

    protected static function booted(): void
    {
        static::creating(function (ProfileResume $resume): void {
            $resume->public_id ??= (string) Str::ulid();
        });
    }

    protected function casts(): array
    {
        return ['size_bytes' => 'integer', 'uploaded_at' => 'immutable_datetime'];
    }

    protected $hidden = ['disk', 'path'];

    /** @return BelongsTo<UserProfile, $this> */
    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
