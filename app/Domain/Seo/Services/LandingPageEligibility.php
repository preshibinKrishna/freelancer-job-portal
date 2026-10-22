<?php

declare(strict_types=1);

namespace App\Domain\Seo\Services;

use App\Models\EngagementProject;
use App\Models\FreelancerProfile;
use App\Models\Job;
use App\Models\Location;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Builder;

final class LandingPageEligibility
{
    public function skill(Skill $skill): bool
    {
        $jobs = Job::query()->discoverable()->whereHas('skills', fn (Builder $query) => $query->whereKey($skill->id))->count();
        $projects = EngagementProject::query()->discoverable()->whereHas('skills', fn (Builder $query) => $query->whereKey($skill->id))->count();
        $freelancers = FreelancerProfile::query()->discoverable()->whereHas('talentProfile.skills', fn (Builder $query) => $query->whereKey($skill->id))->count();

        return $jobs + $projects + $freelancers >= (int) config('seo.minimum_landing_items', 3);
    }

    public function location(Location $location): bool
    {
        $locations = collect([$location]);
        $frontier = collect([$location->id]);
        for ($depth = 0; $depth < 5 && $frontier->isNotEmpty(); $depth++) {
            $children = Location::query()->whereIn('parent_id', $frontier)->get();
            $locations = $locations->merge($children);
            $frontier = $children->pluck('id');
        }
        if ($location->type->value === 'it_park' && $location->parent !== null) {
            $locations->push($location->parent);
        }
        $locationIds = $locations->pluck('id')->unique()->values();
        $parkIds = $locations->filter(fn (Location $item): bool => $item->type->value === 'it_park')->pluck('id')->unique()->values();
        $jobs = Job::query()->discoverable()->where(fn (Builder $query) => $query->whereIn('location_id', $locationIds)->orWhereIn('it_park_id', $parkIds))->count();
        $projects = EngagementProject::query()->discoverable()->whereIn('location_id', $locationIds)->count();
        $freelancers = FreelancerProfile::query()->discoverable()->whereHas('talentProfile', fn (Builder $profile) => $profile->whereIn('location_id', $locationIds)->orWhereHas('preferredLocations', fn (Builder $preferred) => $preferred->whereIn('locations.id', $locationIds)))->count();

        return $jobs + $projects + $freelancers >= (int) config('seo.minimum_landing_items', 3);
    }
}
