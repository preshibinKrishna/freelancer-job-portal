<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Seo\Services\LandingPageEligibility;
use App\Models\EngagementProject;
use App\Models\FreelancerProfile;
use App\Models\Job;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class LocationPageController extends Controller
{
    public function __invoke(string $path, LandingPageEligibility $eligibility): View
    {
        $segments = explode('/', trim($path, '/'));
        abort_if(count($segments) > 5, 404);

        $location = null;
        foreach ($segments as $slug) {
            abort_if($slug === '' || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug), 404);
            $query = Location::query()->where('slug', $slug);
            $query = $location === null ? $query->whereNull('parent_id') : $query->where('parent_id', $location->id);
            $location = $query->firstOrFail();
        }

        $location->load('parent.parent.parent.parent', 'children');
        $relatedLocations = collect([$location]);
        $frontier = collect([$location->id]);
        for ($depth = 0; $depth < 5 && $frontier->isNotEmpty(); $depth++) {
            $children = Location::query()->whereIn('parent_id', $frontier)->get();
            $relatedLocations = $relatedLocations->merge($children);
            $frontier = $children->pluck('id');
        }
        if ($location->type->value === 'it_park' && $location->parent !== null) {
            $relatedLocations->push($location->parent);
        }
        $locationIds = $relatedLocations->pluck('id')->unique()->values();
        $parkIds = $relatedLocations->filter(fn (Location $related): bool => $related->type->value === 'it_park')->pluck('id')->unique()->values();
        $jobsQuery = Job::query()->discoverable()->where(fn (Builder $query) => $query->whereIn('location_id', $locationIds)->orWhereIn('it_park_id', $parkIds));
        $projectsQuery = EngagementProject::query()->discoverable()->whereIn('location_id', $locationIds);
        $freelancersQuery = FreelancerProfile::query()->discoverable()->whereHas('talentProfile', fn (Builder $profile) => $profile->whereIn('location_id', $locationIds)->orWhereHas('preferredLocations', fn (Builder $preferred) => $preferred->whereIn('locations.id', $locationIds)));
        $jobCount = (clone $jobsQuery)->count();
        $projectCount = (clone $projectsQuery)->count();
        $freelancerCount = (clone $freelancersQuery)->count();
        $description = "Explore {$jobCount} open jobs, {$projectCount} freelance projects, and {$freelancerCount} public technology freelancer profiles connected to {$location->name}.";
        $canonicalUrl = URL::to('/locations/'.$location->path());
        $indexable = $eligibility->location($location);
        $title = match ($location->type->value) {
            'it_park' => $location->name.' Jobs and Technology Talent · M Talent',
            'city' => 'Software Jobs in '.$location->name.' and Nearby Talent · M Talent',
            'state_province' => $location->name.' Software Jobs and Freelance Developers · M Talent',
            default => 'Technology Jobs and Talent in '.$location->name.' · M Talent',
        };

        return view('locations.show', [
            'location' => $location,
            'canonicalUrl' => $canonicalUrl,
            'description' => $description,
            'title' => $title,
            'indexable' => $indexable,
            'jobCount' => $jobCount,
            'jobs' => $jobsQuery->with(['company', 'location', 'itPark', 'skills'])->latest('published_at')->limit(12)->get(),
            'projects' => $projectsQuery->with(['company', 'location', 'skills'])->latest('published_at')->limit(8)->get(),
            'freelancers' => $freelancersQuery->with(['talentProfile.user', 'talentProfile.primaryLocation.parent', 'talentProfile.skills'])->latest()->limit(8)->get(),
            'structuredData' => [
                '@context' => 'https://schema.org',
                '@type' => 'Place',
                'name' => $location->name,
                'url' => $canonicalUrl,
                'description' => $description,
                'containedInPlace' => $location->parent ? ['@type' => 'Place', 'name' => $location->parent->name] : null,
            ],
        ]);
    }
}
