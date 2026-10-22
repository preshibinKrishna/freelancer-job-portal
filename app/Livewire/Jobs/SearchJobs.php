<?php

declare(strict_types=1);

namespace App\Livewire\Jobs;

use App\Enums\EmploymentType;
use App\Enums\LocationType;
use App\Enums\WorkplaceType;
use App\Models\Job;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class SearchJobs extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $locationId = '';

    #[Url(except: '')]
    public string $itParkId = '';

    #[Url(except: '')]
    public string $employmentType = '';

    #[Url(except: '')]
    public string $workplaceType = '';

    #[Url(except: '')]
    public string $experienceMin = '';

    #[Url(except: '')]
    public string $salaryMin = '';

    #[Url(except: '')]
    public string $currency = '';

    #[Url(except: 'newest')]
    public string $sort = 'newest';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'locationId', 'itParkId', 'employmentType', 'workplaceType', 'experienceMin', 'salaryMin', 'currency', 'sort']);
        $this->resetPage();
    }

    public function render(): View
    {
        $query = Job::query()->discoverable()->with(['company', 'location', 'itPark', 'skills']);
        $term = trim($this->search);
        if ($term !== '') {
            $query->where(function (Builder $builder) use ($term): void {
                $builder->where('title', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%')
                    ->orWhereHas('company', fn (Builder $company) => $company->where('name', 'like', '%'.$term.'%'))
                    ->orWhereHas('skills', fn (Builder $skills) => $skills->where('name', 'like', '%'.$term.'%'));
            });
        }

        if ($this->locationId !== '' && ctype_digit($this->locationId)) {
            $query->where('location_id', (int) $this->locationId);
        }
        if ($this->itParkId !== '' && ctype_digit($this->itParkId)) {
            $query->where('it_park_id', (int) $this->itParkId);
        }
        if (EmploymentType::tryFrom($this->employmentType) !== null) {
            $query->where('employment_type', $this->employmentType);
        }
        if (WorkplaceType::tryFrom($this->workplaceType) !== null) {
            $query->where('workplace_type', $this->workplaceType);
        }
        if ($this->experienceMin !== '' && ctype_digit($this->experienceMin)) {
            $query->where(fn (Builder $builder) => $builder->whereNull('experience_max')->orWhere('experience_max', '>=', (int) $this->experienceMin));
        }
        if ($this->salaryMin !== '' && is_numeric($this->salaryMin) && (float) $this->salaryMin >= 0 && preg_match('/^[A-Za-z]{3}$/', $this->currency) === 1) {
            $query->where('salary_max', '>=', (float) $this->salaryMin);
        }
        if (preg_match('/^[A-Za-z]{3}$/', $this->currency) === 1) {
            $query->where('currency', strtoupper($this->currency));
        }

        match ($this->sort) {
            'salary_low' => $query->orderBy('salary_min')->orderByDesc('published_at'),
            'salary_high' => $query->orderByDesc('salary_max')->orderByDesc('published_at'),
            default => $query->orderByDesc('published_at'),
        };

        $jobs = $query->paginate(12);
        $hasFilters = filled($this->search) || $this->locationId !== '' || $this->itParkId !== '' || $this->employmentType !== '' || $this->workplaceType !== '' || $this->experienceMin !== '' || $this->salaryMin !== '' || $this->currency !== '' || $this->sort !== 'newest';
        $page = $jobs->currentPage();
        $canonical = $hasFilters ? route('jobs.index') : route('jobs.index', $page > 1 ? ['page' => $page] : []);
        $structuredData = ['@context' => 'https://schema.org', '@type' => 'ItemList', 'name' => 'Technology jobs on M Talent', 'numberOfItems' => $jobs->total(), 'itemListElement' => $jobs->getCollection()->values()->map(fn (Job $job, int $index): array => ['@type' => 'ListItem', 'position' => (($page - 1) * 12) + $index + 1, 'url' => route('jobs.show', $job->slug), 'name' => $job->title])->all()];

        return view('livewire.jobs.search-jobs', [
            'jobs' => $jobs,
            'locations' => Location::query()->whereIn('type', [LocationType::City, LocationType::Area])->with('parent')->orderBy('name')->get(),
            'parks' => Location::query()->ofType(LocationType::ItPark)->orderBy('name')->get(),
            'employmentTypes' => EmploymentType::cases(),
            'workplaceTypes' => WorkplaceType::cases(),
            // The paginator already ran the matching total-count query.
            'resultCount' => $jobs->total(),
        ])->extends('layouts.livewire', [
            'title' => 'Technology jobs · M Talent',
            'description' => 'Explore thoughtful technology roles from trusted teams.',
            'canonical' => $canonical,
            'robots' => $hasFilters ? 'noindex,follow' : 'index,follow',
            'previousUrl' => ! $hasFilters && $page > 1 ? route('jobs.index', ['page' => $page - 1]) : null,
            'nextUrl' => ! $hasFilters && $page < $jobs->lastPage() ? route('jobs.index', ['page' => $page + 1]) : null,
            'structuredData' => $structuredData,
        ]);
    }
}
