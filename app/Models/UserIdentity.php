<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserIdentityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserIdentity extends Model
{
    /** @use HasFactory<UserIdentityFactory> */
    use HasFactory;

    protected $fillable = ['provider', 'provider_subject', 'email'];

    protected static function newFactory(): UserIdentityFactory
    {
        return UserIdentityFactory::new();
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
