<main id="main-content" class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:py-12">
    <x-seo.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Jobs']]" class="mb-5"/>
    <nav class="mb-8 flex flex-wrap items-center gap-4 border-b border-line pb-4 text-sm" aria-label="Job navigation">@auth<a class="font-medium text-ink-700 hover:text-brand-700" href="{{ route('candidate.saved-jobs.index') }}">Saved jobs</a><a class="font-medium text-ink-700 hover:text-brand-700" href="{{ route('candidate.applications.index') }}">Applications</a><a class="font-medium text-brand-700 hover:underline" href="{{ route('employer.jobs.index') }}">Employer workspace</a>@endauth</nav>
    <header class="mb-8"><p class="bt-eyebrow">M Talent · Jobs</p><h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink-950 sm:text-4xl">Find work worth doing.</h1><p class="mt-3 max-w-2xl text-base leading-7 text-ink-600">Explore thoughtful technology roles from teams building what comes next.</p></header>

    <form wire:submit.prevent class="mb-6 rounded-xl border border-line bg-white p-4 shadow-card sm:p-5" role="search" aria-label="Search technology jobs">
        <label for="jobSearch" class="sr-only">Role, skill, or company</label><div class="flex flex-col gap-3 sm:flex-row"><div class="relative min-w-0 flex-1"><span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-500" aria-hidden="true">⌕</span><input id="jobSearch" type="search" wire:model.live.debounce.350ms="search" placeholder="Role, skill, or company" class="bt-field pl-10" autocomplete="off"></div><button type="button" class="bt-button-primary sm:min-w-36" wire:click="$refresh">Search jobs</button></div>
    </form>

    <div class="grid gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
        <aside class="h-fit rounded-xl border border-line bg-white p-5" aria-labelledby="filter-heading">
            <div class="flex items-center justify-between"><h2 id="filter-heading" class="font-semibold text-ink-950">Refine results</h2><button type="button" wire:click="clearFilters" class="text-sm font-medium text-brand-700 hover:underline">Clear</button></div>
            <div class="mt-5 space-y-5">
                <div><label for="locationFilter" class="bt-label">City or area</label><select id="locationFilter" wire:model.live="locationId" class="bt-field"><option value="">Anywhere</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}@if($location->parent), {{ $location->parent->name }}@endif</option>@endforeach</select></div>
                <div><label for="parkFilter" class="bt-label">IT park</label><select id="parkFilter" wire:model.live="itParkId" class="bt-field"><option value="">Any park</option>@foreach($parks as $park)<option value="{{ $park->id }}">{{ $park->name }}</option>@endforeach</select></div>
                <div><label for="employmentFilter" class="bt-label">Employment type</label><select id="employmentFilter" wire:model.live="employmentType" class="bt-field"><option value="">All types</option>@foreach($employmentTypes as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select></div>
                <div><label for="workplaceFilter" class="bt-label">Workplace</label><select id="workplaceFilter" wire:model.live="workplaceType" class="bt-field"><option value="">Any workplace</option>@foreach($workplaceTypes as $type)<option value="{{ $type->value }}">{{ $type->label() }}</option>@endforeach</select></div>
                <div><label for="experienceFilter" class="bt-label">Minimum experience (years)</label><input id="experienceFilter" type="number" min="0" max="60" wire:model.live.debounce.300ms="experienceMin" class="bt-field" placeholder="Any level"></div>
                <div><label for="salaryFilter" class="bt-label">Minimum salary</label><div class="grid grid-cols-[1fr_5rem] gap-2"><input id="salaryFilter" type="number" min="0" wire:model.live.debounce.400ms="salaryMin" class="bt-field" placeholder="No minimum"><input aria-label="Currency code" type="text" maxlength="3" wire:model.live.debounce.500ms="currency" class="bt-field uppercase" placeholder="Code"></div><p class="bt-help">Use an ISO currency code to narrow salary results.</p></div>
            </div>
        </aside>

        <section aria-labelledby="results-heading">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3"><div><h2 id="results-heading" class="text-lg font-semibold text-ink-950">Technology opportunities</h2><p class="mt-1 text-sm text-ink-600">{{ \Illuminate\Support\Number::format($resultCount, locale: app()->getLocale()) }} {{ \Illuminate\Support\Str::plural('role', $resultCount) }}</p></div><div class="flex items-center gap-2"><label for="sortJobs" class="text-sm text-ink-600">Sort</label><select id="sortJobs" wire:model.live="sort" class="bt-field min-h-10 w-auto py-2"><option value="newest">Most recent</option><option value="salary_low">Salary: low to high</option><option value="salary_high">Salary: high to low</option></select></div></div>
            <div class="space-y-3" wire:loading.class="opacity-50" aria-live="polite" aria-busy="false">
                @forelse($jobs as $job)
                    <x-job-card :job="$job"/>
                @empty
                    <x-empty-state title="No roles match just yet" description="Try broadening your filters or check back soon for new opportunities."/>
                @endforelse
            </div>
            <div class="mt-6">{{ $jobs->links() }}</div>
        </section>
    </div>
</main>
