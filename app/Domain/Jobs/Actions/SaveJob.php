<?php

declare(strict_types=1);

namespace App\Domain\Jobs\Actions;

use App\Enums\JobStatus;
use App\Enums\LocationType;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Job;
use App\Models\Location;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaveJob
{
    /** @param array<string, mixed> $attributes */
    public function execute(User $actor, Company $company, array $attributes, ?Job $job = null): Job
    {
        return DB::transaction(function () use ($actor, $company, $attributes, $job): Job {
            $company = Company::query()->lockForUpdate()->findOrFail($company->id);
            Gate::forUser($actor)->authorize('manageJobs', $company);
            if ($job !== null) {
                $job = Job::query()->lockForUpdate()->findOrFail($job->id);
                if ($job->company_id !== $company->id) {
                    throw ValidationException::withMessages(['companyId' => 'A job cannot be moved to another company after creation.']);
                }
                if ($job->status === JobStatus::Closed) {
                    throw ValidationException::withMessages(['job' => 'Closed jobs cannot be edited. Duplicate this job to start a new listing.']);
                }
            }

            $this->validateRanges($attributes);
            $this->validateLocations($attributes);
            $data = [
                'company_id' => $company->id,
                'created_by' => $job === null ? $actor->id : $job->created_by,
                'title' => trim((string) $attributes['title']),
                'slug' => $this->uniqueSlug((string) $attributes['title'], $job),
                'description' => trim((string) $attributes['description']),
                'employment_type' => $attributes['employment_type'],
                'workplace_type' => $attributes['workplace_type'],
                'experience_min' => $attributes['experience_min'] ?: null,
                'experience_max' => $attributes['experience_max'] ?: null,
                'salary_min' => $attributes['salary_min'] ?: null,
                'salary_max' => $attributes['salary_max'] ?: null,
                'currency' => $attributes['currency'] ? strtoupper((string) $attributes['currency']) : null,
                'location_id' => $attributes['location_id'] ?: null,
                'it_park_id' => $attributes['it_park_id'] ?: null,
                'responsibilities' => $attributes['responsibilities'] ?? [],
                'requirements' => $attributes['requirements'] ?? [],
                'benefits' => $attributes['benefits'] ?? [],
                'application_deadline' => $attributes['application_deadline'] ?: null,
                'visibility' => $attributes['visibility'],
            ];

            if ($job === null) {
                $job = Job::query()->create($data + ['status' => JobStatus::Draft]);
            } else {
                $job->update($data);
            }

            $skillIds = collect($attributes['skills'] ?? [])
                ->map(fn (string $name): string => trim($name))
                ->filter()
                ->unique(fn (string $name): string => mb_strtolower($name))
                ->map(fn (string $name): int => Skill::query()->firstOrCreate(
                    ['slug' => Str::slug($name) ?: 'skill-'.substr(hash('sha256', mb_strtolower($name)), 0, 12)],
                    ['name' => mb_substr($name, 0, 80)],
                )->id)->all();
            $job->skills()->sync($skillIds);

            AuditLog::query()->create([
                'actor_id' => $actor->id,
                'event' => $job->wasRecentlyCreated ? 'job.created' : 'job.updated',
                'metadata' => ['company_id' => $company->id, 'job_id' => $job->public_id],
            ]);

            return $job->fresh(['company', 'location', 'itPark', 'skills']) ?? $job;
        });
    }

    /** @param array<string, mixed> $attributes */
    private function validateLocations(array $attributes): void
    {
        $locationId = $attributes['location_id'] ?? null;
        if ($locationId && ! Location::query()->whereKey($locationId)->whereIn('type', [LocationType::City, LocationType::Area])->exists()) {
            throw ValidationException::withMessages(['locationId' => 'Choose a city or area for the job location.']);
        }

        $itParkId = $attributes['it_park_id'] ?? null;
        if ($itParkId && ! Location::query()->whereKey($itParkId)->where('type', LocationType::ItPark)->exists()) {
            throw ValidationException::withMessages(['itParkId' => 'Choose a location recorded as an IT park.']);
        }
    }

    /** @param array<string, mixed> $attributes */
    private function validateRanges(array $attributes): void
    {
        $experienceMin = $attributes['experience_min'] ?? null;
        $experienceMax = $attributes['experience_max'] ?? null;
        if ($experienceMin !== null && $experienceMax !== null && (int) $experienceMin > (int) $experienceMax) {
            throw ValidationException::withMessages(['experienceMax' => 'Maximum experience must be at least the minimum.']);
        }

        $salaryMin = $attributes['salary_min'] ?? null;
        $salaryMax = $attributes['salary_max'] ?? null;
        if ($salaryMin !== null && $salaryMax !== null && (float) $salaryMin > (float) $salaryMax) {
            throw ValidationException::withMessages(['salaryMax' => 'Maximum salary must be at least the minimum.']);
        }
        if (($salaryMin !== null || $salaryMax !== null) && preg_match('/^[A-Za-z]{3}$/', (string) ($attributes['currency'] ?? '')) !== 1) {
            throw ValidationException::withMessages(['currency' => 'Enter a three-letter currency code when listing a salary.']);
        }
    }

    private function uniqueSlug(string $title, ?Job $ignore = null): string
    {
        $base = Str::slug($title) ?: 'technology-job';
        $slug = $base;
        $suffix = 2;

        while (Job::query()->where('slug', $slug)->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
