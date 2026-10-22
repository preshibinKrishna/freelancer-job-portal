<?php

declare(strict_types=1);

namespace App\Domain\Projects\Actions;

use App\Enums\EngagementProjectStatus;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\EngagementProject;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SaveEngagementProject
{
    /** @param array<string, mixed> $attributes */
    public function execute(User $actor, Company $company, array $attributes, ?EngagementProject $project = null): EngagementProject
    {
        return DB::transaction(function () use ($actor, $attributes, $company, $project): EngagementProject {
            $company = Company::query()->lockForUpdate()->findOrFail($company->id);
            Gate::forUser($actor)->authorize('create', [EngagementProject::class, $company]);
            if ($project !== null) {
                $project = EngagementProject::query()->lockForUpdate()->findOrFail($project->id);
                Gate::forUser($actor)->authorize('update', $project);
                if ($project->company_id !== $company->id) {
                    throw ValidationException::withMessages(['companyId' => 'A project cannot be moved to another company.']);
                }
            }

            $minimum = $attributes['budget_min'] ?? null;
            $maximum = $attributes['budget_max'] ?? null;
            if ($minimum !== null && $maximum !== null && (float) $minimum > (float) $maximum) {
                throw ValidationException::withMessages(['budgetMax' => 'Maximum budget must be at least the minimum.']);
            }
            if (($minimum !== null || $maximum !== null) && preg_match('/^[A-Za-z]{3}$/', (string) ($attributes['currency'] ?? '')) !== 1) {
                throw ValidationException::withMessages(['currency' => 'Enter a three-letter currency code with a budget.']);
            }

            $project ??= new EngagementProject(['company_id' => $company->id, 'created_by' => $actor->id, 'status' => EngagementProjectStatus::Draft]);
            $project->fill([
                'title' => trim((string) $attributes['title']), 'description' => trim((string) $attributes['description']),
                'budget_min' => $minimum, 'budget_max' => $maximum, 'currency' => strtoupper((string) $attributes['currency']),
                'pricing_type' => $attributes['pricing_type'], 'estimated_duration_days' => $attributes['estimated_duration_days'] ?: null,
                'experience_min' => $attributes['experience_min'] ?: null, 'location_id' => $attributes['location_id'] ?: null,
                'is_remote' => (bool) $attributes['is_remote'], 'application_deadline' => $attributes['application_deadline'] ?: null,
            ]);
            $project->company()->associate($company);
            $project->save();

            $skillIds = collect($attributes['skills'] ?? [])->map(function (string $name): int {
                $name = trim($name);
                $slug = Str::slug($name) ?: 'skill-'.substr(hash('sha256', mb_strtolower($name)), 0, 12);

                return Skill::query()->firstOrCreate(['slug' => $slug], ['name' => mb_substr($name, 0, 80)])->id;
            })->unique()->values()->all();
            $project->skills()->sync($skillIds);

            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $project->id, 'event' => $project->wasRecentlyCreated ? 'project.created' : 'project.updated', 'metadata' => ['project_public_id' => $project->public_id]]);

            return $project->fresh(['company', 'skills', 'location', 'attachments']) ?? $project;
        });
    }
}
