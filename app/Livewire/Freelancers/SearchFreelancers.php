<?php

declare(strict_types=1);

namespace App\Livewire\Freelancers;

use App\Enums\LocationType;
use App\Models\FreelancerProfile;
use App\Models\Location;
use App\Models\ProjectCategory;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SearchFreelancers extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $skill = '';

    #[Url]
    public string $location = '';

    #[Url]
    public string $hourlyMin = '';

    #[Url]
    public string $hourlyMax = '';

    #[Url]
    public string $currency = '';

    #[Url]
    public string $experience = '';

    #[Url]
    public string $availability = '';

    #[Url]
    public string $rating = '';

    #[Url]
    public string $verified = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $sort = 'recent';

    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'skill', 'location', 'hourlyMin', 'hourlyMax', 'currency', 'experience', 'availability', 'rating', 'verified', 'category', 'sort']);
        $this->resetPage();
    }

    public function render(): View
    {
        $query = FreelancerProfile::query()
            ->discoverable()
            ->with(['talentProfile.user', 'talentProfile.primaryLocation.parent', 'talentProfile.skills', 'categories'])
            ->withCount([
                'projects as verified_completed_projects_count' => fn (Builder $builder): Builder => $builder->where('verification_status', 'verified')->whereNotNull('completed_at')->where('is_visible', true),
                'reviews as approved_reviews_count' => fn (Builder $builder): Builder => $builder->where('moderation_status', 'approved'),
            ])
            ->withAvg(['reviews as approved_reviews_avg_rating' => fn (Builder $builder): Builder => $builder->where('moderation_status', 'approved')], 'rating');

        if (filled($this->search)) {
            $term = trim($this->search);
            $query->whereHas('talentProfile', function (Builder $profile) use ($term): void {
                $profile->whereHas('user', fn (Builder $user) => $user->where('name', 'like', "%{$term}%"))
                    ->orWhere('headline', 'like', "%{$term}%")
                    ->orWhere('bio', 'like', "%{$term}%")
                    ->orWhereHas('skills', fn (Builder $skills) => $skills->where('name', 'like', "%{$term}%"));
            });
        }
        if ($this->skill !== '') {
            $query->whereHas('talentProfile.skills', fn (Builder $builder) => $builder->where('slug', $this->skill));
        }
        if ($this->location !== '') {
            $query->whereHas('talentProfile.primaryLocation', fn (Builder $builder) => $builder->where('public_id', $this->location));
        }
        if ($this->currency !== '' && ($this->hourlyMin !== '' || $this->hourlyMax !== '')) {
            $query->where('currency', $this->currency);
        } elseif ($this->hourlyMin !== '' || $this->hourlyMax !== '') {
            $query->where('currency', '__select_currency__');
        }
        if ($this->hourlyMin !== '') {
            $query->where('hourly_rate', '>=', $this->hourlyMin);
        }
        if ($this->hourlyMax !== '') {
            $query->where('hourly_rate', '<=', $this->hourlyMax);
        }
        if ($this->experience !== '') {
            $query->whereHas('talentProfile', fn (Builder $builder) => $builder->where('experience_years', '>=', (int) $this->experience));
        }
        if ($this->availability !== '') {
            $query->whereHas('talentProfile', fn (Builder $builder) => $builder->where('availability', $this->availability));
        }
        if ($this->verified === 'yes') {
            $query->where('verification_status', 'verified');
        }
        if ($this->category !== '') {
            $query->whereHas('categories', fn (Builder $builder) => $builder->where('slug', $this->category));
        }
        if ($this->rating !== '') {
            $query->whereHas('reviews', fn (Builder $reviews): Builder => $reviews
                ->where('moderation_status', 'approved')
                ->where('rating', '>=', (float) $this->rating));
        }

        match ($this->sort) {
            'rating' => $query->orderByDesc('approved_reviews_avg_rating')->orderByDesc('approved_reviews_count'),
            'rate_low' => $query->orderBy('hourly_rate')->orderBy('project_rate'),
            'experience' => $query->orderByDesc(DB::table('user_profiles')->select('experience_years')->whereColumn('user_profiles.id', 'freelancer_profiles.user_profile_id')->limit(1)),
            default => $query->latest('created_at'),
        };

        $freelancers = $query->paginate(12);
        $hasFilters = filled($this->search) || $this->skill !== '' || $this->location !== '' || $this->hourlyMin !== '' || $this->hourlyMax !== '' || $this->currency !== '' || $this->experience !== '' || $this->availability !== '' || $this->rating !== '' || $this->verified !== '' || $this->category !== '' || $this->sort !== 'recent';
        $page = $freelancers->currentPage();
        $canonical = $hasFilters ? route('freelancers.index') : route('freelancers.index', $page > 1 ? ['page' => $page] : []);
        $structuredData = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'Public technology freelancer profiles', 'numberOfItems' => $freelancers->total(), 'itemListElement' => $freelancers->getCollection()->values()->map(fn (FreelancerProfile $freelancer, int $index): array => ['@type' => 'ListItem', 'position' => (($page - 1) * 12) + $index + 1, 'url' => route('freelancers.show', $freelancer->talentProfile->profile_slug), 'name' => $freelancer->talentProfile->user->name])->all()];

        return view('livewire.freelancers.search-freelancers', [
            'freelancers' => $freelancers,
            'skills' => Skill::query()->whereHas('userProfiles.freelancerProfile', fn (Builder $builder) => $builder->where('is_listed', true))->orderBy('name')->get(),
            'categories' => ProjectCategory::query()->orderBy('sort_order')->orderBy('name')->get(),
            'locations' => Location::query()->whereIn('type', [LocationType::City, LocationType::Area])->with('parent')->orderBy('name')->get(),
            'currencies' => FreelancerProfile::query()->discoverable()->whereNotNull('currency')->distinct()->orderBy('currency')->pluck('currency'),
        ])->extends('layouts.livewire', [
            'title' => 'Find independent technology talent · M Talent',
            'description' => 'Explore thoughtfully presented technology freelancers by skills, availability, location, experience, and work.',
            'canonical' => route('freelancers.index'),
            'robots' => $hasFilters ? 'noindex,follow' : 'index,follow',
            'previousUrl' => ! $hasFilters && $page > 1 ? route('freelancers.index', ['page' => $page - 1]) : null,
            'nextUrl' => ! $hasFilters && $page < $freelancers->lastPage() ? route('freelancers.index', ['page' => $page + 1]) : null,
            'structuredData' => $structuredData,
        ]);
    }
}
