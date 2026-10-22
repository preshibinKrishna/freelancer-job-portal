<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LocationType;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** @property LocationType $type */
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    protected $fillable = ['parent_id', 'type', 'name', 'slug', 'country_code'];

    protected static function booted(): void
    {
        static::saving(function (Location $location): void {
            $location->public_id ??= (string) Str::ulid();
            $location->slug = Str::slug($location->name);
            $location->root_slug = $location->type === LocationType::Country ? $location->slug : null;
        });
    }

    protected function casts(): array
    {
        return ['type' => LocationType::class];
    }

    protected static function newFactory(): LocationFactory
    {
        return LocationFactory::new();
    }

    /** @return BelongsTo<Location, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Location, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('name');
    }

    /** @return HasMany<LocationAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(LocationAssignment::class);
    }

    /** @return HasMany<UserProfile, $this> */
    public function primaryProfiles(): HasMany
    {
        return $this->hasMany(UserProfile::class, 'location_id');
    }

    public function scopeOfType(Builder $query, LocationType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function path(): string
    {
        $segments = [];
        $location = $this;

        do {
            array_unshift($segments, $location->slug);
            $location = $location->parent;
        } while ($location !== null);

        return implode('/', $segments);
    }
}
