<?php

declare(strict_types=1);

namespace App\Domain\Recruitment\Actions;

use App\Enums\LocationType;
use App\Enums\RecruitmentRequestStatus;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Location;
use App\Models\RecruitmentRequest;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreateRecruitmentRequest
{
    /** @param array<string, mixed> $data */
    public function execute(User $actor, Company $company, array $data): RecruitmentRequest
    {
        return DB::transaction(function () use ($actor, $company, $data): RecruitmentRequest {
            $company = Company::query()->lockForUpdate()->findOrFail($company->id);
            Gate::forUser($actor)->authorize('create', [RecruitmentRequest::class, $company]);
            if (($data['experience_min'] ?? null) !== null && ($data['experience_max'] ?? null) !== null && $data['experience_min'] > $data['experience_max']) {
                throw ValidationException::withMessages(['experienceMax' => 'Maximum experience must not be below minimum experience.']);
            }
            if (($data['salary_min'] ?? null) !== null && ($data['salary_max'] ?? null) !== null && (float) $data['salary_min'] > (float) $data['salary_max']) {
                throw ValidationException::withMessages(['salaryMax' => 'Maximum salary must not be below minimum salary.']);
            }
            if (! empty($data['location_id']) && ! Location::query()->whereKey($data['location_id'])->whereIn('type', [LocationType::City, LocationType::Area])->exists()) {
                throw ValidationException::withMessages(['locationId' => 'Choose a city or area for the location.']);
            }
            $request = RecruitmentRequest::query()->create([
                'company_id' => $company->id, 'created_by' => $actor->id, 'position' => trim($data['position']),
                'number_of_openings' => $data['number_of_openings'], 'experience_min' => $data['experience_min'] ?: null,
                'experience_max' => $data['experience_max'] ?: null, 'salary_min' => $data['salary_min'] ?: null,
                'salary_max' => $data['salary_max'] ?: null, 'currency' => filled($data['salary_min']) || filled($data['salary_max']) ? strtoupper($data['currency']) : null,
                'location_id' => $data['location_id'] ?: null, 'workplace_type' => $data['workplace_type'],
                'notice_period_days' => $data['notice_period_days'] ?: null, 'urgency' => $data['urgency'],
                'description' => trim($data['description']), 'status' => RecruitmentRequestStatus::Submitted, 'submitted_at' => now(),
            ]);
            $skillIds = collect($data['skills'])->map(fn (string $name): string => trim($name))->filter()->unique(fn (string $name): string => mb_strtolower($name))->map(fn (string $name): int => Skill::query()->firstOrCreate(
                ['slug' => Str::slug($name) ?: 'skill-'.substr(hash('sha256', mb_strtolower($name)), 0, 12)],
                ['name' => mb_substr($name, 0, 80)],
            )->id)->all();
            $request->skills()->sync($skillIds);
            app(RecordRecruitmentActivity::class)->execute($request, null, $actor, 'request.submitted', ['position' => $request->position]);
            AuditLog::query()->create(['actor_id' => $actor->id, 'subject_id' => $request->id, 'event' => 'recruitment.request_submitted', 'metadata' => ['company_id' => $company->id, 'request_public_id' => $request->public_id]]);

            return $request->load(['company', 'skills']);
        });
    }
}
