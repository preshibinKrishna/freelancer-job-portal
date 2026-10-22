<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CompanyMemberRole;
use App\Enums\CompanyMembershipStatus;
use Database\Factories\CompanyMembershipFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property CompanyMemberRole $role
 * @property CompanyMembershipStatus $status
 */
class CompanyMembership extends Model
{
    /** @use HasFactory<CompanyMembershipFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'user_id', 'role', 'status', 'invited_by'];

    protected function casts(): array
    {
        return ['role' => CompanyMemberRole::class, 'status' => CompanyMembershipStatus::class];
    }

    protected static function newFactory(): CompanyMembershipFactory
    {
        return CompanyMembershipFactory::new();
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<User, $this> */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }
}
