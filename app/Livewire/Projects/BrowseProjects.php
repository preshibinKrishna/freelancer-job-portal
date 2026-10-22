<?php

declare(strict_types=1);

namespace App\Livewire\Projects;

use App\Enums\LocationType;
use App\Enums\ProjectPricingType;
use App\Models\EngagementProject;
use App\Models\Location;
use App\Models\Skill;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class BrowseProjects extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url]
    public string $skill = '';

    #[Url]
    public string $location = '';

    #[Url]
    public string $pricing = '';

    #[Url]
    public string $currency = '';

    #[Url]
    public string $budgetMin = '';

    #[Url]
    public string $budgetMax = '';

    #[Url]
    public string $experience = '';

    #[Url]
    public bool $remote = false;

    public function updated(string $property): void
    {
        if ($property !== 'page') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'skill', 'location', 'pricing', 'currency', 'budgetMin', 'budgetMax', 'experience', 'remote']);
        $this->resetPage();
    }

    public function render(): View
    {
        $query = EngagementProject::query()->discoverable()->with(['company', 'skills', 'location.parent']);
        if (filled($this->search)) {
            $term = trim($this->search);
            $query->where(fn (Builder $builder) => $builder->where('title', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhereHas('company', fn (Builder $company) => $company->where('name', 'like', "%{$term}%"))
                ->orWhereHas('skills', fn (Builder $skills) => $skills->where('name', 'like', "%{$term}%")));
        }
        if ($this->skill !== '') {
            $query->whereHas('skills', fn (Builder $builder) => $builder->where('slug', $this->skill));
        }
        if ($this->location !== '') {
            $query->whereHas('location', fn (Builder $builder) => $builder->where('public_id', $this->location));
        }
        if ($this->pricing !== '') {
            $query->where('pricing_type', $this->pricing);
        }
        if ($this->currency !== '') {
            $query->where('currency', $this->currency);
        }
        if ($this->budgetMin !== '') {
            $query->where(fn (Builder $builder) => $builder->whereNull('budget_max')->orWhere('budget_max', '>=', $this->budgetMin));
        }
        if ($this->budgetMax !== '') {
            $query->where(fn (Builder $builder) => $builder->whereNull('budget_min')->orWhere('budget_min', '<=', $this->budgetMax));
        }
        if ($this->experience !== '') {
            $query->where(fn (Builder $builder) => $builder->whereNull('experience_min')->orWhere('experience_min', '<=', (int) $this->experience));
        }
        if ($this->remote) {
            $query->where('is_remote', true);
        }

        $projects = $query->latest('published_at')->paginate(12);
        $hasFilters = filled($this->search) || $this->skill !== '' || $this->location !== '' || $this->pricing !== '' || $this->currency !== '' || $this->budgetMin !== '' || $this->budgetMax !== '' || $this->experience !== '' || $this->remote;
        $page = $projects->currentPage();
        $canonical = $hasFilters ? route('projects.index') : route('projects.index', $page > 1 ? ['page' => $page] : []);
        $structuredData = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'Freelance technology projects on M Talent', 'numberOfItems' => $projects->total(), 'itemListElement' => $projects->getCollection()->values()->map(fn (EngagementProject $project, int $index): array => ['@type' => 'ListItem', 'position' => (($page - 1) * 12) + $index + 1, 'url' => route('projects.show', $project), 'name' => $project->title])->all()];

        return view('livewire.projects.browse-projects', [
            'projects' => $projects,
            'skills' => Skill::query()->whereHas('engagementProjects', fn (Builder $project) => $project->discoverable())->orderBy('name')->get(),
            'locations' => Location::query()->whereIn('type', [LocationType::City, LocationType::Area])->with('parent')->orderBy('name')->get(),
            'pricingTypes' => ProjectPricingType::cases(),
            'currencies' => EngagementProject::query()->discoverable()->distinct()->orderBy('currency')->pluck('currency'),
        ])->extends('layouts.livewire', [
            'title' => 'Explore freelance projects · M Talent',
            'description' => 'Find active freelance technology projects with clear briefs, budgets, and skills.',
            'canonical' => $canonical,
            'robots' => $hasFilters ? 'noindex,follow' : 'index,follow',
            'previousUrl' => ! $hasFilters && $page > 1 ? route('projects.index', ['page' => $page - 1]) : null,
            'nextUrl' => ! $hasFilters && $page < $projects->lastPage() ? route('projects.index', ['page' => $page + 1]) : null,
            'structuredData' => $structuredData,
        ]);
    }
}
