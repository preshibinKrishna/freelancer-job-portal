<?php

declare(strict_types=1);

namespace App\Domain\Matching\Services;

use App\Domain\Matching\Contracts\MatchingEngine;
use App\Domain\Matching\Data\MatchResult;
use App\Domain\Profiles\Services\ProfileCompleteness;
use App\Enums\FreelancerVerificationStatus;
use App\Enums\VerificationStatus;
use App\Enums\WorkplaceType;
use App\Models\EngagementProject;
use App\Models\FreelancerProfile;
use App\Models\Job;
use App\Models\RecruitmentRequest;
use App\Models\UserProfile;

final class TalentMatchingService
{
    public function __construct(
        private readonly MatchingEngine $engine,
        private readonly ProfileCompleteness $completeness,
    ) {}

    public function matchJobCandidate(Job $job, UserProfile $candidate): MatchResult
    {
        return $this->match(
            $this->candidate($candidate),
            $job->skills()->pluck('name')->all(),
            $job->experience_min,
            $job->location_id,
            $job->workplace_type === WorkplaceType::Remote,
            $job->salary_min === null && $job->salary_max === null ? null : [(float) $job->salary_min, (float) $job->salary_max],
            $job->currency,
        );
    }

    public function matchProjectFreelancer(EngagementProject $project, FreelancerProfile $freelancer): MatchResult
    {
        $candidate = $this->candidate($freelancer->talentProfile);
        $rate = $freelancer->project_rate ?? $freelancer->hourly_rate;
        $candidate['compensation'] = $rate === null ? null : (float) $rate;
        $candidate['currency'] = $freelancer->currency;

        return $this->match(
            $candidate,
            $project->skills()->pluck('name')->all(),
            $project->experience_min,
            $project->location_id,
            $project->is_remote,
            $project->budget_min === null && $project->budget_max === null ? null : [(float) $project->budget_min, (float) $project->budget_max],
            $project->currency,
        );
    }

    public function matchRecruitmentRequestCandidate(RecruitmentRequest $request, UserProfile $candidate): MatchResult
    {
        return $this->match(
            $this->candidate($candidate),
            $request->skills()->pluck('name')->all(),
            $request->experience_min,
            $request->location_id,
            $request->workplace_type === WorkplaceType::Remote,
            $request->salary_min === null && $request->salary_max === null ? null : [(float) $request->salary_min, (float) $request->salary_max],
            $request->currency,
        );
    }

    /** @return array{skills: list<string>, experience: int|null, location: int|null, preferred_locations: list<int>, availability: string|null, compensation: float|null, currency: string|null, completeness: float, verified: bool} */
    private function candidate(UserProfile $profile): array
    {
        $user = $profile->user;
        $freelancer = $profile->freelancerProfile;
        $verified = $user->verifications()->where('status', VerificationStatus::Approved)
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists()
            || ($freelancer?->verification_status === FreelancerVerificationStatus::Verified);

        return [
            'skills' => $profile->skills()->pluck('name')->all(),
            'experience' => $profile->experience_years,
            'location' => $profile->location_id,
            'preferred_locations' => $profile->preferredLocations()->pluck('locations.id')->map(fn ($id): int => (int) $id)->all(),
            'availability' => $profile->availability,
            'compensation' => $profile->expected_salary === null ? null : (float) $profile->expected_salary,
            'currency' => $profile->expected_salary_currency,
            'completeness' => $this->completeness->assess($profile)['percentage'] / 100,
            'verified' => $verified,
        ];
    }

    /** @param list<string> $requiredSkills @param array{skills: list<string>, experience: int|null, location: int|null, preferred_locations: list<int>, availability: string|null, compensation: float|null, currency: string|null, completeness: float, verified: bool} $candidate @param array{0: float, 1: float}|null $range */
    private function match(array $candidate, array $requiredSkills, ?int $minimumExperience, ?int $locationId, bool $remote, ?array $range, ?string $currency): MatchResult
    {
        $candidateSkills = collect($candidate['skills'])->keyBy(fn (string $skill): string => mb_strtolower($skill));
        $matched = [];
        $missing = [];
        foreach ($requiredSkills as $skill) {
            if ($candidateSkills->has(mb_strtolower($skill))) {
                $matched[] = $candidateSkills->get(mb_strtolower($skill));
            } else {
                $missing[] = $skill;
            }
        }
        $skillScore = $requiredSkills === [] ? null : count($matched) / count($requiredSkills);
        $tolerance = max(1, (int) config('matching.experience_tolerance_years', 3));
        $experienceScore = $minimumExperience === null ? null : ($candidate['experience'] === null ? 0.0 : min(1.0, max(0.0, ($candidate['experience'] - $minimumExperience + $tolerance) / $tolerance)));
        $locationScore = $locationId === null || $remote ? null : (($candidate['location'] === $locationId || in_array($locationId, $candidate['preferred_locations'], true)) ? 1.0 : 0.0);
        $availabilityScore = $candidate['availability'] === null ? null : (float) config('matching.availability.'.$candidate['availability'], 0);
        $compensationScore = null;
        if ($range !== null && $candidate['compensation'] !== null && $currency !== null && $candidate['currency'] === $currency) {
            [$minimum, $maximum] = $range;
            $expected = $candidate['compensation'];
            $compensationScore = ($minimum === 0.0 && $maximum === 0.0) || (($minimum === 0.0 || $expected >= $minimum) && ($maximum === 0.0 || $expected <= $maximum)) ? 1.0 : 0.0;
        }

        return $this->engine->rank([
            'skills' => $skillScore,
            'experience' => $experienceScore,
            'location' => $locationScore,
            'availability' => $availabilityScore,
            'compensation' => $compensationScore,
            'completeness' => $candidate['completeness'],
            'verification' => $candidate['verified'] ? 1.0 : 0.0,
        ], $matched, $missing);
    }
}
