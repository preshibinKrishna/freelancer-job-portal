<main id="main-content" class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:py-14">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><p class="bt-eyebrow">Employer workspace</p><h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink-950">Your companies</h1><p class="mt-2 text-ink-600">Build a credible home for your team and the people you hope to meet.</p></div>
        <div class="flex flex-wrap gap-3"><a class="bt-button-primary" href="{{ route('employer.talent-requirements.create') }}">Find professionals for a solution</a><a class="bt-button-secondary" href="{{ route('employer.jobs.index') }}">Manage jobs</a><a class="bt-button-secondary" href="{{ route('employer.recruitment-requests.index') }}">Recruitment requests</a><a class="bt-button-secondary" href="{{ route('employer.recruitment-requests.create') }}">Request recruitment help</a><a class="bt-button-secondary" href="{{ route('employer.companies.create') }}">Create a company profile</a></div>
    </div>
    @if (session('status'))<x-alert class="mt-6" tone="success">{{ session('status') }}</x-alert>@endif
    @if ($companies->isEmpty())
        <div class="mt-10 rounded-xl border border-dashed border-line bg-white px-6 py-14 text-center">
            <div class="mx-auto grid size-14 place-items-center rounded-xl bg-brand-100 text-xl font-semibold text-brand-700" aria-hidden="true">M</div>
            <h2 class="mt-5 text-lg font-semibold text-ink-950">Your employer workspace starts here</h2>
            <p class="mx-auto mt-2 max-w-lg text-sm leading-6 text-ink-600">Create your company profile, add teammates and choose which details you want to make public.</p>
            <a class="mt-6 inline-flex min-h-11 items-center justify-center rounded-md bg-brand-700 px-4 text-sm font-semibold text-white hover:bg-brand-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700" href="{{ route('employer.companies.create') }}">Create company profile</a>
        </div>
    @else
        <ul class="mt-8 grid gap-4 md:grid-cols-2">
            @foreach ($companies as $company)
                <li class="rounded-xl border border-line bg-white p-5 shadow-card sm:p-6">
                    <div class="flex items-start gap-4"><x-company-logo :name="$company->name" size="lg" :src="$company->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path) : null"/><div class="min-w-0 flex-1"><div class="flex flex-wrap items-center gap-2"><h2 class="truncate text-lg font-semibold text-ink-950">{{ $company->name }}</h2><x-company-verification-badge :status="$company->verification_status"/></div><p class="mt-1 text-sm text-ink-600">{{ $company->industry ?? 'Industry not set' }}@if($company->headquarters), {{ $company->headquarters->name }}@endif</p></div></div>
                    <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4"><span class="text-sm text-ink-600">{{ $company->isPublished() ? 'Public profile live' : 'Draft profile' }}</span><div class="flex items-center gap-4"><a class="text-sm font-semibold text-brand-700 hover:underline" href="{{ route('employer.companies.edit', $company) }}">Manage</a>@if($company->isPublished())<a class="text-sm font-semibold text-ink-700 hover:underline" href="{{ route('companies.show', $company->slug) }}">View profile</a>@endif</div></div>
                </li>
            @endforeach
        </ul>
    @endif
</main>
