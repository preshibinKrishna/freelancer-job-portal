<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ProfileEducationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileEducation extends Model
{
    /** @use HasFactory<ProfileEducationFactory> */
    use HasFactory;

    protected $table = 'profile_educations';

    protected $fillable = ['institution', 'degree', 'field_of_study', 'started_at', 'ended_at'];

    protected function casts(): array
    {
        return ['started_at' => 'date', 'ended_at' => 'date'];
    }

    /** @return BelongsTo<UserProfile, $this> */
    public function userProfile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class);
    }
}
