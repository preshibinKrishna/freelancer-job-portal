<?php

declare(strict_types=1);

use App\Domain\Matching\Services\TalentMatchingService;
use App\Models\EngagementProject;
use App\Models\FreelancerProfile;
use App\Models\Job;
use App\Models\RecruitmentRequest;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('jobs are scored deterministically with matched and missing skills and factor explanations', function (): void {
    $candidate = UserProfile::factory()->for(User::factory())->create([
        'experience_years' => 5,
        'availability' => 'actively_looking',
        'expected_salary' => 90000,
        'expected_salary_currency' => 'USD',
    ]);
    $php = Skill::factory()->create(['name' => 'PHP']);
    $laravel = Skill::factory()->create(['name' => 'Laravel']);
    $redis = Skill::factory()->create(['name' => 'Redis']);
    $candidate->skills()->attach([$php->id, $laravel->id]);
    $job = Job::factory()->create(['experience_min' => 3, 'salary_min' => 80000, 'salary_max' => 100000, 'currency' => 'USD']);
    $job->skills()->attach([$php->id, $laravel->id, $redis->id]);

    $service = app(TalentMatchingService::class);
    $result = $service->matchJobCandidate($job, $candidate);
    $repeat = $service->matchJobCandidate($job, $candidate);

    expect($result->score)->toBe($repeat->score)
        ->and($result->matchedSkills)->toBe(['PHP', 'Laravel'])
        ->and($result->missingSkills)->toBe(['Redis'])
        ->and($result->factorScores)->toHaveKeys(['skills', 'experience', 'availability', 'compensation', 'completeness', 'verification'])
        ->and($result->explanation)->not->toBeEmpty()
        ->and($result->toArray())->toHaveKey('match_score');
});

test('scoring weights are centrally configurable', function (): void {
    config(['matching.weights.skills' => 100, 'matching.weights.experience' => 0, 'matching.weights.location' => 0, 'matching.weights.availability' => 0, 'matching.weights.compensation' => 0, 'matching.weights.completeness' => 0, 'matching.weights.verification' => 0]);
    $candidate = UserProfile::factory()->for(User::factory())->create();
    $job = Job::factory()->create();
    $skill = Skill::factory()->create(['name' => 'Laravel']);
    $job->skills()->attach($skill);

    $result = app(TalentMatchingService::class)->matchJobCandidate($job, $candidate);

    expect($result->score)->toBe(0)
        ->and($result->missingSkills)->toBe(['Laravel']);
});

test('projects match freelancers and recruitment requests match candidates', function (): void {
    $owner = User::factory()->create();
    $candidate = UserProfile::factory()->for($owner)->create(['experience_years' => 4]);
    $freelancer = FreelancerProfile::factory()->create(['user_profile_id' => $candidate->id]);
    $project = EngagementProject::factory()->create();
    $recruitmentRequest = RecruitmentRequest::query()->create([
        'company_id' => $project->company_id,
        'created_by' => $project->created_by,
        'position' => 'Backend developer',
        'workplace_type' => 'remote',
        'description' => 'Build backend services.',
        'submitted_at' => now(),
    ]);

    $service = app(TalentMatchingService::class);
    expect($service->matchProjectFreelancer($project, $freelancer)->score)->toBeGreaterThanOrEqual(0)
        ->and($service->matchRecruitmentRequestCandidate($recruitmentRequest, $candidate)->score)->toBeGreaterThanOrEqual(0);
});
