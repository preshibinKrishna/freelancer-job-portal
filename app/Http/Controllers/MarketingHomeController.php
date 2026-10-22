<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\FreelancerProfile;
use App\Models\Job;
use Illuminate\View\View;

class MarketingHomeController
{
    public function __invoke(): View
    {
        $jobs = Job::query()->discoverable()
            ->with(['company', 'location', 'skills'])
            ->orderByDesc('published_at')->limit(4)->get();

        $freelancers = FreelancerProfile::query()->discoverable()
            ->with(['talentProfile.user', 'talentProfile.primaryLocation.parent', 'talentProfile.skills'])
            ->withCount(['reviews as approved_reviews_count' => fn ($query) => $query->where('moderation_status', 'approved')])
            ->withAvg(['reviews as approved_reviews_avg_rating' => fn ($query) => $query->where('moderation_status', 'approved')], 'rating')
            ->orderByDesc('verified_at')->limit(3)->get();

        $companies = Company::query()->whereNotNull('published_at')
            ->with('headquarters.parent')->orderByDesc('published_at')->limit(5)->get();

        return view('home', compact('jobs', 'freelancers', 'companies'));
    }
}
