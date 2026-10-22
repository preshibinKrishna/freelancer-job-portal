<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SkillFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/** @property string $public_id */
class Skill extends Model
{
    /** @use HasFactory<SkillFactory> */
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    protected static function booted(): void
    {
        static::creating(function (Skill $skill): void {
            $skill->public_id ??= (string) Str::ulid();
            $skill->slug = Str::slug($skill->slug ?: $skill->name);
        });
    }

    protected static function newFactory(): SkillFactory
    {
        return SkillFactory::new();
    }

    /** @return BelongsToMany<UserProfile, $this> */
    public function userProfiles(): BelongsToMany
    {
        return $this->belongsToMany(UserProfile::class, 'talent_profile_skills');
    }

    /** @return BelongsToMany<EngagementProject, $this> */
    public function engagementProjects(): BelongsToMany
    {
        return $this->belongsToMany(EngagementProject::class, 'engagement_project_skills');
    }

    /** @return BelongsToMany<RecruitmentRequest, $this> */
    public function recruitmentRequests(): BelongsToMany
    {
        return $this->belongsToMany(RecruitmentRequest::class, 'recruitment_request_skills');
    }

    /** @return BelongsToMany<Job, $this> */
    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'job_skill');
    }
}
