<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\UserProfile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TalentProfileController extends Controller
{
    public function show(string $slug): View
    {
        $profile = UserProfile::query()->where('profile_slug', $slug)->with([
            'user', 'primaryLocation.parent', 'skills', 'experiences', 'educations', 'certifications', 'languages', 'portfolioLinks',
        ])->firstOrFail();
        Gate::authorize('viewPublic', $profile);

        $indexable = $profile->visibility === 'public' && $profile->user->isActive() && $profile->user->hasVerifiedEmail();
        $canonical = route('talent.show', $profile->profile_slug);
        $description = Str::limit(strip_tags($profile->bio ?: 'Public professional profile for '.$profile->user->name), 155);

        return view('profiles.show', [
            'profile' => $profile,
            'user' => $profile->user,
            'indexable' => $indexable,
            'canonical' => $canonical,
            'description' => $description,
            'structuredData' => $indexable ? [
                '@context' => 'https://schema.org',
                '@type' => 'Person',
                'name' => $profile->user->name,
                'url' => $canonical,
                'jobTitle' => $profile->headline,
                'description' => strip_tags((string) $profile->bio),
                'knowsAbout' => $profile->skills->pluck('name')->values()->all(),
                'address' => $profile->primaryLocation ? ['@type' => 'PostalAddress', 'addressLocality' => $profile->primaryLocation->name, 'addressRegion' => $profile->primaryLocation->parent?->name] : null,
                'image' => $profile->photo_path ? route('talent.photo', $profile->user->public_id) : null,
            ] : null,
        ]);
    }
}
