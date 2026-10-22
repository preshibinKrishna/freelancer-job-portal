<main id="main-content" class="mx-auto max-w-5xl px-4 py-9 sm:px-6 lg:py-12">
    <div><p class="bt-eyebrow">Candidate workspace</p><h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink-950">Saved jobs</h1><p class="mt-2 text-sm leading-6 text-ink-600">Keep a shortlist of roles you’d like to come back to.</p></div>
    @if($jobs->isEmpty())<div class="mt-7"><x-empty-state title="Nothing saved yet" description="Save a role while you explore, then find it here when you’re ready."/><a class="bt-button-primary mt-5" href="{{ route('jobs.index') }}">Explore jobs</a></div>@else<ul class="mt-7 space-y-3">@foreach($jobs as $job)<li><x-job-card :job="$job"/></li>@endforeach</ul><div class="mt-6">{{ $jobs->links() }}</div>@endif
</main>
