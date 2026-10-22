<x-layouts.app :title="$title" :canonical="$canonicalUrl" :description="$description" :robots="$indexable ? 'index,follow' : 'noindex,follow'" ogType="website" :structuredData="$structuredData">
    @php
        $ancestors = [];
        $ancestor = $location->parent;
        while ($ancestor !== null) { array_unshift($ancestors, $ancestor); $ancestor = $ancestor->parent; }
    @endphp
    <main id="main-content" class="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:py-16">
        <x-seo.breadcrumbs :items="array_merge([['label' => 'Home', 'url' => route('home')], ['label' => 'Locations']], collect($ancestors)->map(fn ($ancestor) => ['label' => $ancestor->name, 'url' => route('locations.show', ['path' => $ancestor->path()])])->all(), [['label' => $location->name]])"/>
        <section class="mt-10 max-w-3xl">
            <p class="text-sm font-semibold uppercase tracking-[0.12em] text-brand-700">{{ $location->type->label() }}</p>
            <h1 class="mt-3 text-4xl font-semibold tracking-tight text-ink-950 sm:text-5xl">@if($location->type->value === 'it_park'){{ $location->name }} jobs and technology talent@elseif($location->type->value === 'city')Software jobs in {{ $location->name }}@elseif($location->type->value === 'state_province'){{ $location->name }} software jobs and freelance developers@elseTechnology jobs and talent in {{ $location->name }}@endif</h1>
            <p class="mt-5 max-w-2xl text-lg leading-8 text-ink-600">Explore current software roles, freelance projects, and public technology profiles connected to {{ $location->name }}. Results are based on opportunities and profiles listed on M Talent.</p>
        </section>
        @if($jobs->isNotEmpty())<section class="mt-12" aria-labelledby="location-jobs-heading"><div class="flex flex-wrap items-end justify-between gap-3"><div><p class="bt-eyebrow">{{ number_format($jobCount) }} open opportunities</p><h2 id="location-jobs-heading" class="mt-1 text-2xl font-semibold">{{ $location->type->value === 'state_province' ? 'Software jobs in '.$location->name : 'Technology jobs in '.$location->name }}</h2></div><a class="text-sm font-semibold text-brand-700 hover:underline" href="{{ route('jobs.index') }}">Browse all jobs</a></div><ul class="mt-4 space-y-3">@foreach($jobs as $job)<li><x-job-card :job="$job"/></li>@endforeach</ul></section>@endif
        @if($projects->isNotEmpty())<section class="mt-12" aria-labelledby="location-projects-heading"><p class="bt-eyebrow">Freelance opportunities</p><h2 id="location-projects-heading" class="mt-1 text-2xl font-semibold">Projects connected to {{ $location->name }}</h2><ul class="mt-4 grid gap-4 md:grid-cols-2">@foreach($projects as $project)<li><x-engagement-project-card :project="$project"/></li>@endforeach</ul></section>@endif
        @if($freelancers->isNotEmpty())<section class="mt-12" aria-labelledby="location-talent-heading"><div class="flex flex-wrap items-end justify-between gap-3"><div><p class="bt-eyebrow">Public professional profiles</p><h2 id="location-talent-heading" class="mt-1 text-2xl font-semibold">Freelance developers and technology talent in {{ $location->name }}</h2></div><a class="text-sm font-semibold text-brand-700 hover:underline" href="{{ route('freelancers.index') }}">Explore freelancers</a></div><ul class="mt-4 grid gap-4 md:grid-cols-2">@foreach($freelancers as $freelancer)<li><x-freelancer-card :freelancer="$freelancer"/></li>@endforeach</ul></section>@endif
        @if ($location->children->isNotEmpty())
            <section class="mt-12" aria-labelledby="sub-locations-heading">
                <h2 id="sub-locations-heading" class="text-lg font-semibold text-ink-950">Explore nearby locations</h2>
                <ul class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($location->children as $child)
                        <li><a class="block rounded-lg border border-line bg-white p-4 font-medium text-ink-950 transition hover:border-brand-300 hover:bg-brand-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700" href="{{ route('locations.show', ['path' => $child->path()]) }}">{{ $child->name }}<span class="mt-1 block text-sm font-normal text-ink-600">{{ $child->type->label() }}</span></a></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </main>
</x-layouts.app>
