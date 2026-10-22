<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProjectCategoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/** @property string $public_id */
class ProjectCategory extends Model
{
    /** @use HasFactory<ProjectCategoryFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description', 'sort_order'];

    protected static function booted(): void
    {
        static::creating(function (ProjectCategory $category): void {
            $category->public_id ??= (string) Str::ulid();
        });
    }

    protected static function newFactory(): ProjectCategoryFactory
    {
        return ProjectCategoryFactory::new();
    }

    /** @return HasMany<FreelancerProject, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(FreelancerProject::class);
    }

    /** @return BelongsToMany<FreelancerProfile, $this> */
    public function freelancerProfiles(): BelongsToMany
    {
        return $this->belongsToMany(FreelancerProfile::class, 'freelancer_profile_categories');
    }
}
