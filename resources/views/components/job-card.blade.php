@props(['job'])
<article class="group relative rounded-xl border border-line bg-white p-5 transition hover:border-brand-200 hover:shadow-card sm:p-6">
    <div class="flex items-start gap-4">
        <x-company-logo :name="$job->company->name" size="lg" :src="$job->company->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($job->company->logo_path) : null"/>
        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-2"><h2 class="text-lg font-semibold leading-6 text-ink-950"><a class="rounded-sm outline-none after:absolute after:inset-0 focus-visible:ring-2 focus-visible:ring-brand-600 group-hover:text-brand-700" href="{{ route('jobs.show', $job->slug) }}">{{ $job->title }}</a></h2>@if($job->company->verification_status === \App\Enums\CompanyVerificationStatus::Verified)<span class="relative z-10"><x-company-verification-badge :status="$job->company->verification_status"/></span>@endif</div>
            <p class="mt-1 text-sm font-medium text-ink-700">{{ $job->company->name }}</p>
            <div class="mt-3 flex flex-wrap gap-x-4 gap-y-2 text-sm text-ink-600"><span>{{ $job->employment_type->label() }}</span><span>{{ $job->workplace_type->label() }}</span>@if($job->location)<span>{{ $job->location->name }}</span>@endif @if($job->itPark)<span>{{ $job->itPark->name }}</span>@endif</div>
            <div class="mt-4 flex flex-wrap items-center justify-between gap-3"><span class="text-sm font-medium text-ink-700">@if($job->salary_min !== null || $job->salary_max !== null){{ $job->salary_min !== null ? \Illuminate\Support\Number::currency((float) $job->salary_min, $job->currency, app()->getLocale()) : '—' }} – {{ $job->salary_max !== null ? \Illuminate\Support\Number::currency((float) $job->salary_max, $job->currency, app()->getLocale()) : '—' }}@else Salary not listed @endif</span><span class="text-xs text-ink-500">{{ $job->published_at?->diffForHumans() }}</span></div>
            @if($job->skills->isNotEmpty())<ul class="relative z-10 mt-4 flex flex-wrap gap-2" aria-label="Required skills">@foreach($job->skills->take(5) as $skill)<li><x-badge tone="neutral">{{ $skill->name }}</x-badge></li>@endforeach</ul>@endif
        </div>
    </div>
</article>
