<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Seo\Services\LandingPageEligibility;
use App\Models\EngagementProject;
use App\Models\FreelancerProfile;
use App\Models\Job;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class SkillPageController extends Controller
{
    public function __invoke(Skill $skill, LandingPageEligibility $eligibility): View
    {
        $jobs = Job::query()->discoverable()->whereHas('skills', fn (Builder $query) => $query->whereKey($skill->id))->with(['company', 'location', 'itPark', 'skills'])->latest('published_at')->limit(8)->get();
        $projects = EngagementProject::query()->discoverable()->whereHas('skills', fn (Builder $query) => $query->whereKey($skill->id))->with(['company', 'location', 'skills'])->latest('published_at')->limit(8)->get();
        $freelancers = FreelancerProfile::query()->discoverable()->whereHas('talentProfile.skills', fn (Builder $query) => $query->whereKey($skill->id))->with(['talentProfile.user', 'talentProfile.primaryLocation', 'talentProfile.skills'])->latest()->limit(8)->get();
        $jobCount = Job::query()->discoverable()->whereHas('skills', fn (Builder $query) => $query->whereKey($skill->id))->count();
        $projectCount = EngagementProject::query()->discoverable()->whereHas('skills', fn (Builder $query) => $query->whereKey($skill->id))->count();
        $freelancerCount = FreelancerProfile::query()->discoverable()->whereHas('talentProfile.skills', fn (Builder $query) => $query->whereKey($skill->id))->count();
        $total = $jobCount + $projectCount + $freelancerCount;
        $canonical = route('skills.show', $skill->slug);
        $description = "Browse {$jobCount} open roles, {$projectCount} freelance projects, and {$freelancerCount} public freelancer profiles mentioning {$skill->name} on M Talent.";
        $indexable = $eligibility->skill($skill);

        return view('skills.show', [
            'skill' => $skill,
            'jobs' => $jobs,
            'projects' => $projects,
            'freelancers' => $freelancers,
            'canonical' => $canonical,
            'description' => $description,
            'indexable' => $indexable,
            'structuredData' => [
                '@context' => 'https://schema.org',
                '@type' => 'DefinedTerm',
                'name' => $skill->name,
                'url' => $canonical,
                'description' => $description,
                'inDefinedTermSet' => ['@type' => 'DefinedTermSet', 'name' => 'M Talent skills'],
            ],
        ]);
    }
}
