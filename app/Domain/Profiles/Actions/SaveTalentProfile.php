<?php

declare(strict_types=1);

namespace App\Domain\Profiles\Actions;

use App\Models\AuditLog;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SaveTalentProfile
{
    /** @param array<string, mixed> $attributes */
    public function execute(User $user, array $attributes): UserProfile
    {
        return DB::transaction(function () use ($user, $attributes): UserProfile {
            $profile = $user->profile()->firstOrNew();
            $oldVisibility = $profile->visibility ?? 'private';
            $user->update(['name' => $attributes['name']]);

            $profile->fill([
                'headline' => $attributes['headline'] ?? null,
                'bio' => $attributes['bio'] ?? null,
                'location_id' => $attributes['location_id'] ?: null,
                'visibility' => $attributes['visibility'],
                'availability' => $attributes['availability'] ?: null,
                'experience_years' => $attributes['experience_years'] ?: null,
                'notice_period_days' => $attributes['notice_period_days'] ?: null,
                'expected_salary' => $attributes['expected_salary'] ?: null,
                'expected_salary_currency' => $attributes['expected_salary'] ? strtoupper((string) $attributes['expected_salary_currency']) : null,
                'preferred_work_types' => array_values(array_unique($attributes['preferred_work_types'] ?? [])),
                'linkedin_url' => $attributes['linkedin_url'] ?: null,
                'github_url' => $attributes['github_url'] ?: null,
            ]);

            if ($profile->visibility === 'public') {
                $profile->profile_slug = Str::slug((string) $user->name).'-'.strtolower(substr($user->public_id, -8));
            }
            $profile->save();

            $skillIds = collect($attributes['skills'] ?? [])
                ->map(fn (mixed $name): string => trim((string) $name))
                ->filter()
                ->unique(fn (string $name): string => Str::lower($name))
                ->map(function (string $name): int {
                    $slug = Str::slug($name);
                    $slug = $slug !== '' ? $slug : 'skill-'.Str::lower(Str::random(8));
                    $skill = Skill::query()->firstOrCreate(['name' => $name], ['slug' => $slug]);

                    return $skill->id;
                })->all();
            $profile->skills()->sync($skillIds);

            $locationIds = collect($attributes['preferred_location_ids'] ?? [])->map(static fn (mixed $id): int => (int) $id)->unique()->all();
            $profile->preferredLocations()->sync($locationIds);

            $this->replaceRecords($profile, 'experiences', $attributes['experiences'] ?? [], ['title', 'company']);
            $this->replaceRecords($profile, 'educations', $attributes['educations'] ?? [], ['institution']);
            $this->replaceRecords($profile, 'certifications', $attributes['certifications'] ?? [], ['name']);
            $this->replaceRecords($profile, 'languages', $attributes['languages'] ?? [], ['language']);
            $this->replaceRecords($profile, 'portfolioLinks', $attributes['portfolio_links'] ?? [], ['title', 'url']);

            AuditLog::query()->create([
                'actor_id' => $user->id,
                'subject_id' => $user->id,
                'event' => $oldVisibility === $profile->visibility ? 'talent_profile.updated' : 'talent_profile.visibility_changed',
                'metadata' => ['visibility' => $profile->visibility],
            ]);

            return $profile->load(['skills', 'primaryLocation', 'preferredLocations', 'experiences', 'educations', 'certifications', 'languages', 'portfolioLinks', 'resumes']);
        });
    }

    /** @param array<int, array<string, mixed>> $rows @param list<string> $required */
    private function replaceRecords(UserProfile $profile, string $relation, array $rows, array $required): void
    {
        $rows = array_values(array_filter($rows, static fn (array $row): bool => collect($row)->contains(static fn (mixed $value): bool => filled($value))));
        foreach ($rows as $index => $row) {
            foreach ($required as $field) {
                if (blank($row[$field] ?? null)) {
                    throw ValidationException::withMessages(["{$relation}.{$index}.{$field}" => 'This field is required when adding an item.']);
                }
            }
        }

        $relationQuery = $profile->{$relation}();
        $relationQuery->delete();
        if ($rows !== []) {
            $relationQuery->createMany($rows);
        }
    }
}
