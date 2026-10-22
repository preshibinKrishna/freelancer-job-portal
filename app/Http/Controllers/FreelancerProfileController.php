<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\FreelancerProfile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class FreelancerProfileController extends Controller
{
    public function show(string $slug): View
    {
        $profile = FreelancerProfile::query()
            ->whereHas('talentProfile', fn ($query) => $query->where('profile_slug', $slug))
            ->with([
                'talentProfile.user.verifications' => fn ($query) => $query->where('status', 'approved')->where(fn ($expires) => $expires->whereNull('expires_at')->orWhere('expires_at', '>', now())), 'talentProfile.primaryLocation.parent', 'talentProfile.skills',
                'talentProfile.experiences', 'talentProfile.educations', 'talentProfile.certifications',
                'talentProfile.languages', 'talentProfile.portfolioLinks', 'categories',
                'projects' => fn ($query) => $query->where('is_visible', true)->with(['category', 'requestedCompany', 'review.company'])->orderByDesc('is_featured')->orderByDesc('completed_at'),
                'reviews' => fn ($query) => $query->where('moderation_status', 'approved')->with(['project', 'company']),
            ])
            ->withCount(['reviews as approved_reviews_count' => fn ($query) => $query->where('moderation_status', 'approved')])
            ->withAvg(['reviews as approved_reviews_avg_rating' => fn ($query) => $query->where('moderation_status', 'approved')], 'rating')
            ->firstOrFail();
        abort_unless(Gate::allows('view', $profile), 404);

        $isIndexable = $profile->is_listed
            && $profile->talentProfile->visibility === 'public'
            && $profile->talentProfile->user->isActive()
            && $profile->talentProfile->user->hasVerifiedEmail();
        $canonical = route('freelancers.show', $profile->talentProfile->profile_slug);
        $description = Str::limit(strip_tags($profile->talentProfile->bio ?: 'Public technology freelancer profile for '.$profile->talentProfile->user->name), 155);

        return view('freelancers.show', [
            'freelancer' => $profile,
            'profile' => $profile->talentProfile,
            'user' => $profile->talentProfile->user,
            'indexable' => $isIndexable,
            'canonical' => $canonical,
            'description' => $description,
            'structuredData' => $isIndexable ? [
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'name' => $profile->talentProfile->user->name,
                'url' => $canonical,
                'jobTitle' => $profile->talentProfile->headline,
                'description' => strip_tags((string) $profile->talentProfile->bio),
                'knowsAbout' => $profile->talentProfile->skills->pluck('name')->values()->all(),
                'address' => $profile->talentProfile->primaryLocation ? ['@type' => 'PostalAddress', 'addressLocality' => $profile->talentProfile->primaryLocation->name, 'addressRegion' => $profile->talentProfile->primaryLocation->parent?->name] : null,
                'image' => $profile->talentProfile->photo_path ? route('talent.photo', $profile->talentProfile->user->public_id) : null,
            ] : null,
        ]);
    }
}
