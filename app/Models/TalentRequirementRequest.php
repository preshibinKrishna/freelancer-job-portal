<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\TalentRequirementRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/** @property array<string, string> $details @property list<string> $suggested_skills */
class TalentRequirementRequest extends Model
{
    /** @use HasFactory<TalentRequirementRequestFactory> */
    use HasFactory;

    protected $fillable = ['company_id', 'created_by', 'category', 'solution_type', 'details', 'suggested_skills', 'submitted_at'];

    protected function casts(): array
    {
        return ['details' => 'array', 'suggested_skills' => 'array', 'submitted_at' => 'immutable_datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn (TalentRequirementRequest $request) => $request->public_id ??= (string) Str::ulid());
    }

    protected static function newFactory(): TalentRequirementRequestFactory
    {
        return TalentRequirementRequestFactory::new();
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
