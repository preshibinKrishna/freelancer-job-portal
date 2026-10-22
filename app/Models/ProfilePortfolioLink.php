<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProfilePortfolioLinkFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfilePortfolioLink extends Model
{
    /** @use HasFactory<ProfilePortfolioLinkFactory> */
    use HasFactory;

    protected $fillable = ['title', 'url', 'description'];

    /** @return BelongsTo<UserProfile, $this> */
    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class);
    }
}
