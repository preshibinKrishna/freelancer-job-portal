<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProfileLanguageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileLanguage extends Model
{
    /** @use HasFactory<ProfileLanguageFactory> */
    use HasFactory;

    protected $fillable = ['language', 'proficiency'];

    /** @return BelongsTo<UserProfile, $this> */
    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class);
    }
}
