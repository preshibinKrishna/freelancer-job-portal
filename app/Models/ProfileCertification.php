<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProfileCertificationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileCertification extends Model
{
    /** @use HasFactory<ProfileCertificationFactory> */
    use HasFactory;

    protected $fillable = ['name', 'issuer', 'credential_url', 'issued_at', 'expires_at'];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'expires_at' => 'date'];
    }

    /** @return BelongsTo<UserProfile, $this> */
    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class);
    }
}
