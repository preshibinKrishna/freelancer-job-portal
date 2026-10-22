<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Seo\Services\LandingPageEligibility;
use App\Enums\EngagementProjectStatus;
use App\Models\Company;
use App\Models\EngagementProject;
use App\Models\FreelancerProfile;
use App\Models\Job;
use App\Models\Location;
use App\Models\Skill;
use Carbon\Carbon;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(LandingPageEligibility $eligibility): Response
    {
        $urls = $this->urls($eligibility);
        if (count($urls) > 45000) {
            $sitemaps = collect(range(1, (int) ceil(count($urls) / 45000)))->map(fn (int $part): string => route('sitemap.part', $part))->all();

            return response()->view('seo.sitemap-index', ['sitemaps' => $sitemaps])->header('Content-Type', 'application/xml; charset=UTF-8');
        }

        return response()->view('seo.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    public function part(int $part, LandingPageEligibility $eligibility): Response
    {
        $urls = array_slice($this->urls($eligibility), ($part - 1) * 45000, 45000);
        abort_if($part < 1 || $urls === [], 404);

        return response()->view('seo.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /** @return list<array{loc: string, lastmod: Carbon|null}> */
    private function urls(LandingPageEligibility $eligibility): array
    {
        $urls = [
            ['loc' => route('home'), 'lastmod' => null],
            ['loc' => route('jobs.index'), 'lastmod' => null],
            ['loc' => route('projects.index'), 'lastmod' => null],
            ['loc' => route('freelancers.index'), 'lastmod' => null],
        ];
        Job::query()->discoverable()->select(['slug', 'updated_at'])->orderBy('id')->get()->each(function (Job $job) use (&$urls): void {
            $urls[] = ['loc' => route('jobs.show', $job->slug), 'lastmod' => $job->updated_at];
        });
        Company::query()->whereNotNull('published_at')->select(['slug', 'updated_at'])->orderBy('id')->get()->each(function (Company $company) use (&$urls): void {
            $urls[] = ['loc' => route('companies.show', $company->slug), 'lastmod' => $company->updated_at];
        });
        FreelancerProfile::query()->discoverable()->with('talentProfile')->select(['id', 'updated_at'])->orderBy('id')->get()->each(function (FreelancerProfile $freelancer) use (&$urls): void {
            if ($freelancer->talentProfile?->profile_slug) {
                $urls[] = ['loc' => route('freelancers.show', $freelancer->talentProfile->profile_slug), 'lastmod' => $freelancer->updated_at];
            }
        });
        EngagementProject::query()->discoverable()->where('status', EngagementProjectStatus::Published)->select(['public_id', 'updated_at'])->orderBy('id')->get()->each(function (EngagementProject $project) use (&$urls): void {
            $urls[] = ['loc' => route('projects.show', $project), 'lastmod' => $project->updated_at];
        });
        Skill::query()->select(['id', 'slug', 'updated_at'])->orderBy('id')->get()->each(function (Skill $skill) use (&$urls, $eligibility): void {
            if ($eligibility->skill($skill)) {
                $urls[] = ['loc' => route('skills.show', $skill->slug), 'lastmod' => $skill->updated_at];
            }
        });
        Location::query()->with('parent.parent.parent.parent')->orderBy('id')->get()->each(function (Location $location) use (&$urls, $eligibility): void {
            if ($eligibility->location($location)) {
                $urls[] = ['loc' => route('locations.show', ['path' => $location->path()]), 'lastmod' => $location->updated_at];
            }
        });

        return $urls;
    }
}
