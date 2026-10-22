<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProfileResume;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProfileResume> */
class ProfileResumeFactory extends Factory
{
    protected $model = ProfileResume::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return ['user_profile_id' => UserProfile::factory()->for(User::factory()), 'disk' => 'local', 'path' => 'talent/fake/resumes/resume.pdf', 'original_filename' => 'resume.pdf', 'mime_type' => 'application/pdf', 'size_bytes' => 1024, 'scan_status' => 'pending', 'uploaded_at' => now()];
    }
}
