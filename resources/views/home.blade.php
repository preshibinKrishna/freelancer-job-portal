<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Build your technology career, find trusted talent, and discover freelance opportunities with MTalent.">
    <meta name="robots" content="index,follow">
    <link rel="canonical" href="{{ route('home') }}">
    <meta property="og:site_name" content="MTalent">
    <meta property="og:type" content="website">
    <meta property="og:title" content="MTalent — Technology careers, talent and freelance work">
    <meta property="og:description" content="Build your technology career, find trusted talent, and discover freelance opportunities with MTalent.">
    <meta property="og:url" content="{{ route('home') }}">
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="MTalent — Technology careers, talent and freelance work">
    <meta name="twitter:description" content="Build your technology career, find trusted talent, and discover freelance opportunities with MTalent.">
    <script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@graph' => [['@type' => 'Organization', 'name' => 'MTalent', 'url' => route('home')], ['@type' => 'WebSite', 'name' => 'MTalent', 'url' => route('home'), 'potentialAction' => ['@type' => 'SearchAction', 'target' => route('jobs.index').'?q={search_term_string}', 'query-input' => 'required name=search_term_string']]]], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
    <title>MTalent — Technology careers, talent and freelance work</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-ink-950 antialiased">
    <a class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-ink-950 focus:px-4 focus:py-3 focus:text-white" href="#main-content">Skip to content</a>

    <header class="border-b border-line bg-white">
        <div class="bt-shell flex min-h-16 items-center justify-between gap-5">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2.5" aria-label="MTalent home">
                <span class="grid size-9 place-items-center rounded-lg bg-brand-700 text-lg font-semibold text-white">M</span>
                <span class="text-lg font-semibold tracking-tight">MTalent</span>
            </a>
            <nav class="hidden items-center gap-8 text-sm font-medium text-ink-600 lg:flex" aria-label="Main navigation">
                <a class="transition hover:text-brand-700" href="{{ route('jobs.index') }}">Careers</a>
                <a class="transition hover:text-brand-700" href="{{ route('freelancers.index') }}">Freelancers</a>
                <a class="transition hover:text-brand-700" href="#how-it-works">How it works</a>
            </nav>
            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                @auth
                    <a class="hidden rounded-md px-3 py-2 text-sm font-medium text-ink-700 hover:bg-slate-50 sm:inline-flex" href="{{ route('messages.index') }}">Messages</a>
                    <a class="bt-button-primary min-h-10 px-3 sm:px-4" href="{{ route('profile.edit') }}">My workspace</a>
                @else
                    <a class="rounded-md px-2 py-2 text-sm font-medium text-ink-700 hover:text-brand-700 sm:px-3" href="{{ route('login') }}">Sign in</a>
                    <a class="bt-button-primary min-h-10 px-3 sm:px-4" href="{{ route('register') }}">Get started</a>
                @endauth
            </div>
        </div>
        <nav class="flex gap-6 overflow-x-auto border-t border-line px-4 py-2.5 text-xs font-medium text-ink-600 lg:hidden" aria-label="Mobile navigation">
            <a class="shrink-0" href="{{ route('jobs.index') }}">Careers</a>
            <a class="shrink-0" href="{{ route('freelancers.index') }}">Freelancers</a>
            <a class="shrink-0" href="#how-it-works">How it works</a>
        </nav>
    </header>

    <main id="main-content">
        <section class="relative overflow-hidden bg-[#f4f8f6]" aria-labelledby="hero-heading">
            <div class="pointer-events-none absolute -right-36 -top-48 size-[34rem] rounded-full border border-brand-700/5" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -right-16 -top-28 size-[24rem] rounded-full border border-brand-700/5" aria-hidden="true"></div>
            <div class="bt-shell relative py-16 sm:py-20 lg:py-24">
                <div class="max-w-4xl">
                    <p class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-[.14em] text-brand-700">
                        <span class="size-1.5 rounded-full bg-brand-600" aria-hidden="true"></span>
                        Technology work, thoughtfully connected
                    </p>
                    <h1 id="hero-heading" class="mt-5 max-w-4xl text-4xl font-semibold leading-[1.06] tracking-[-.055em] text-ink-950 sm:text-6xl lg:text-7xl">
                        Find good work. <span class="text-brand-700">Build what’s next.</span>
                    </h1>
                    <p class="mt-5 max-w-2xl text-base leading-7 text-ink-600 sm:text-lg sm:leading-8">
                        Discover technology careers, work with independent experts, or find the people who can move your business forward.
                    </p>
                    <div class="mt-7 flex flex-wrap items-center gap-x-6 gap-y-3">
                        <a href="{{ route('jobs.index') }}" class="bt-button-primary group">Find a job <span class="ml-2 transition-transform group-hover:translate-x-1" aria-hidden="true">→</span></a>
                        <a href="{{ route('freelancers.index') }}" class="text-sm font-semibold text-ink-700 transition hover:text-brand-700">Hire a freelancer <span class="ml-1" aria-hidden="true">↗</span></a>
                        <a href="{{ route('register', ['account_type' => 'employer']) }}" class="text-sm font-semibold text-ink-700 transition hover:text-brand-700">Hire talent <span class="ml-1" aria-hidden="true">↗</span></a>
                    </div>
                </div>

                <form action="{{ route('jobs.index') }}" method="GET" class="mt-10 max-w-4xl rounded-xl border border-line bg-white p-3 shadow-float sm:flex sm:items-center sm:gap-3 sm:p-3.5" role="search" aria-label="Search technology jobs">
                    <label class="sr-only" for="homepage-search">Role, skill, or company</label>
                    <div class="relative min-w-0 flex-1">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-ink-500" aria-hidden="true">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                        </span>
                        <input id="homepage-search" name="q" type="search" class="bt-field min-h-12 border-0 pl-11 shadow-none focus:ring-0" placeholder="Search roles, skills, or companies" autocomplete="off">
                    </div>
                    <button class="bt-button-primary mt-2 min-h-12 w-full sm:mt-0 sm:w-auto sm:px-7" type="submit">Search jobs <span class="ml-2" aria-hidden="true">→</span></button>
                </form>
                <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                    <span class="mr-1 text-ink-500">Popular:</span>
                    <a href="{{ route('jobs.index', ['q' => 'Laravel']) }}" class="rounded-full border border-line bg-white/70 px-3 py-1.5 text-ink-700 transition hover:border-brand-100 hover:text-brand-700">Laravel</a>
                    <a href="{{ route('jobs.index', ['q' => 'Product']) }}" class="rounded-full border border-line bg-white/70 px-3 py-1.5 text-ink-700 transition hover:border-brand-100 hover:text-brand-700">Product</a>
                    <a href="{{ route('jobs.index', ['q' => 'Remote']) }}" class="rounded-full border border-line bg-white/70 px-3 py-1.5 text-ink-700 transition hover:border-brand-100 hover:text-brand-700">Remote</a>
                </div>
            </div>
        </section>

        <section class="bt-shell py-12 sm:py-16" aria-labelledby="pathways-heading">
            <div class="mb-7 max-w-2xl">
                <p class="bt-eyebrow">One network, three ways forward</p>
                <h2 id="pathways-heading" class="bt-section-title mt-2">Choose your next move.</h2>
            </div>
            <div class="grid divide-y divide-line border-y border-line md:grid-cols-3 md:divide-x md:divide-y-0">
                <a href="{{ route('jobs.index') }}" class="group py-5 md:pr-7 md:py-6">
                    <span class="text-xs font-semibold tracking-[.12em] text-ink-400">01 / CAREERS</span>
                    <span class="mt-3 flex items-center justify-between gap-4"><span class="text-lg font-semibold tracking-tight">Find a role that fits.</span><span class="text-ink-400 transition group-hover:translate-x-1 group-hover:text-brand-700" aria-hidden="true">→</span></span>
                    <span class="mt-1.5 block text-sm leading-6 text-ink-600">Explore opportunities from technology teams.</span>
                </a>
                <a href="{{ route('freelancers.index') }}" class="group py-5 md:px-7 md:py-6">
                    <span class="text-xs font-semibold tracking-[.12em] text-ink-400">02 / FREELANCE</span>
                    <span class="mt-3 flex items-center justify-between gap-4"><span class="text-lg font-semibold tracking-tight">Find independent experts.</span><span class="text-ink-400 transition group-hover:translate-x-1 group-hover:text-brand-700" aria-hidden="true">→</span></span>
                    <span class="mt-1.5 block text-sm leading-6 text-ink-600">Browse skilled professionals and their work.</span>
                </a>
                <a href="{{ route('register', ['account_type' => 'employer']) }}" class="group py-5 md:pl-7 md:py-6">
                    <span class="text-xs font-semibold tracking-[.12em] text-ink-400">03 / HIRING</span>
                    <span class="mt-3 flex items-center justify-between gap-4"><span class="text-lg font-semibold tracking-tight">Build your team.</span><span class="text-ink-400 transition group-hover:translate-x-1 group-hover:text-brand-700" aria-hidden="true">→</span></span>
                    <span class="mt-1.5 block text-sm leading-6 text-ink-600">Find people for your next role or project.</span>
                </a>
            </div>
        </section>

        <section class="border-y border-line bg-[#f8faf9] py-12 sm:py-16" aria-labelledby="jobs-heading">
            <div class="bt-shell">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="bt-eyebrow">Featured opportunities</p>
                        <h2 id="jobs-heading" class="bt-section-title mt-2">Good work is out there.</h2>
                    </div>
                    <a href="{{ route('jobs.index') }}" class="inline-flex min-h-10 items-center gap-2 self-start text-sm font-semibold text-brand-700 hover:underline sm:self-auto">Browse all jobs <span aria-hidden="true">→</span></a>
                </div>
                @if($jobs->isNotEmpty())
                    <div class="mt-7 divide-y divide-line border-y border-line bg-white px-4 sm:px-6">
                        @foreach($jobs as $job)
                            <article class="group grid gap-3 py-5 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:gap-8">
                                <div class="flex min-w-0 items-start gap-4">
                                    <x-company-logo :name="$job->company->name" size="md" :src="$job->company->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($job->company->logo_path) : null"/>
                                    <div class="min-w-0">
                                        <h3 class="text-base font-semibold tracking-tight"><a class="hover:text-brand-700" href="{{ route('jobs.show', $job->slug) }}">{{ $job->title }}</a></h3>
                                        <p class="mt-1 text-sm text-ink-600">{{ $job->company->name }}@if($job->location)<span class="mx-1.5 text-ink-300">·</span>{{ $job->location->name }}@endif</p>
                                        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-ink-500"><span>{{ $job->employment_type->label() }}</span><span>{{ $job->workplace_type->label() }}</span>@if($job->salary_min || $job->salary_max)<span>{{ $job->currency }} {{ $job->salary_min ?? '—' }}–{{ $job->salary_max ?? '—' }}</span>@endif</div>
                                    </div>
                                </div>
                                <a href="{{ route('jobs.show', $job->slug) }}" class="inline-flex min-h-9 items-center gap-2 pl-16 text-sm font-semibold text-brand-700 sm:pl-0">View role <span class="transition-transform group-hover:translate-x-1" aria-hidden="true">→</span></a>
                            </article>
                        @endforeach
                    </div>
                @else
                    <p class="mt-7 border-y border-line bg-white px-5 py-6 text-sm text-ink-600">New opportunities are on the way. <a href="{{ route('jobs.index') }}" class="font-semibold text-brand-700 underline">Browse the job board</a>.</p>
                @endif
            </div>
        </section>

        <section class="bt-shell py-12 sm:py-16" aria-labelledby="freelancers-heading">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="bt-eyebrow">Independent talent</p>
                    <h2 id="freelancers-heading" class="bt-section-title mt-2">Meet people who make things happen.</h2>
                    <p class="mt-2 max-w-xl text-sm leading-6 text-ink-600">Explore professional portfolios, skills, and working styles.</p>
                </div>
                <a href="{{ route('freelancers.index') }}" class="inline-flex min-h-10 items-center gap-2 self-start text-sm font-semibold text-brand-700 hover:underline sm:self-auto">Browse freelancers <span aria-hidden="true">→</span></a>
            </div>
            @if($freelancers->isNotEmpty())
                <div class="mt-7 grid gap-x-8 md:grid-cols-3">
                    @foreach($freelancers as $freelancer)
                        @php($profile = $freelancer->talentProfile)
                        <article class="border-t border-line py-5 sm:py-6">
                            <div class="flex items-start gap-3.5">
                                <div class="grid size-12 shrink-0 place-items-center overflow-hidden rounded-full bg-brand-100 text-lg font-semibold text-brand-700">
                                    @if($profile->photo_path)<img src="{{ route('talent.photo', $profile->user->public_id) }}" alt="" class="size-full object-cover">@else{{ mb_substr($profile->user->name, 0, 1) }}@endif
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate font-semibold tracking-tight"><a href="{{ route('freelancers.show', $profile->profile_slug) }}" class="hover:text-brand-700">{{ $profile->user->name }}</a></h3>
                                        @if($freelancer->verification_status === \App\Enums\FreelancerVerificationStatus::Verified)<span class="text-xs font-semibold text-brand-700" title="Verified by MTalent">✓ Verified</span>@endif
                                    </div>
                                    <p class="mt-1 line-clamp-2 text-sm leading-5 text-ink-600">{{ $profile->headline ?: 'Technology professional' }}</p>
                                </div>
                            </div>
                            <p class="mt-3 text-xs text-ink-500">{{ $profile->primaryLocation?->name ?? 'Location flexible' }}@if($freelancer->hourly_rate)<span class="mx-1.5">·</span>{{ \Illuminate\Support\Number::currency((float) $freelancer->hourly_rate, $freelancer->currency) }}/hr @endif</p>
                            @if(($freelancer->approved_reviews_count ?? 0) > 0)<p class="mt-2 text-xs font-medium text-ink-700"><span class="text-amber-600" aria-hidden="true">★</span> {{ number_format((float) $freelancer->approved_reviews_avg_rating, 1) }} <span class="text-ink-500">({{ $freelancer->approved_reviews_count }} reviews)</span></p>@endif
                            @if($profile->skills->isNotEmpty())
                                <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="Skills">@foreach($profile->skills->take(3) as $skill)<li class="rounded-full bg-slate-50 px-2.5 py-1 text-[11px] font-medium text-ink-700">{{ $skill->name }}</li>@endforeach</ul>
                            @endif
                            <a href="{{ route('freelancers.show', $profile->profile_slug) }}" class="mt-4 inline-flex min-h-8 items-center gap-2 text-sm font-semibold text-brand-700">View portfolio <span aria-hidden="true">→</span></a>
                        </article>
                    @endforeach
                </div>
            @else
                <p class="mt-7 border-t border-line pt-5 text-sm text-ink-600">Explore independent professionals in the <a class="font-semibold text-brand-700 underline" href="{{ route('freelancers.index') }}">freelancer directory</a>.</p>
            @endif
        </section>

        <section class="border-y border-line bg-[#f8faf9] py-12 sm:py-16" aria-labelledby="companies-heading">
            <div class="bt-shell">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div><p class="bt-eyebrow">Teams building what comes next</p><h2 id="companies-heading" class="bt-section-title mt-2">Get to know the companies.</h2></div>
                    <a href="{{ route('jobs.index') }}" class="inline-flex min-h-10 items-center gap-2 self-start text-sm font-semibold text-brand-700 hover:underline sm:self-auto">See open roles <span aria-hidden="true">→</span></a>
                </div>
                @if($companies->isNotEmpty())
                    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        @foreach($companies as $company)
                            <a href="{{ route('companies.show', $company->slug) }}" class="group flex min-w-0 items-center gap-3 rounded-lg border border-line bg-white p-3 transition hover:border-brand-100">
                                <x-company-logo :name="$company->name" size="sm" :src="$company->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path) : null"/>
                                <span class="min-w-0"><span class="block truncate text-sm font-semibold group-hover:text-brand-700">{{ $company->name }}</span><span class="mt-0.5 block truncate text-xs text-ink-500">{{ $company->industry ?: ($company->headquarters?->name ?? 'Technology company') }}</span></span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="mt-6 border-t border-line pt-5 text-sm text-ink-600">Discover companies and their open opportunities across the network.</p>
                @endif
            </div>
        </section>

        <section id="how-it-works" class="scroll-mt-10 bg-ink-950 py-12 text-white sm:py-16" aria-labelledby="how-heading">
            <div class="bt-shell">
                <div class="grid gap-8 lg:grid-cols-[.8fr_1.2fr] lg:items-end">
                    <div><p class="text-xs font-semibold uppercase tracking-[.14em] text-[#9bd2c6]">Clear at every step</p><h2 id="how-heading" class="mt-3 max-w-lg text-3xl font-semibold leading-tight tracking-[-.04em] sm:text-4xl">A professional network built on trust.</h2><p class="mt-3 max-w-lg text-sm leading-6 text-slate-300">Profiles, opportunities, and conversations stay connected, with privacy controls and verification built in.</p></div>
                    <ol class="grid gap-5 sm:grid-cols-3">
                        <li class="border-t border-white/20 pt-3"><span class="text-xs font-semibold text-[#9bd2c6]">01</span><h3 class="mt-2 font-semibold">Find your fit</h3><p class="mt-1.5 text-sm leading-6 text-slate-300">Search roles, portfolios, or talent.</p></li>
                        <li class="border-t border-white/20 pt-3"><span class="text-xs font-semibold text-[#9bd2c6]">02</span><h3 class="mt-2 font-semibold">Start a conversation</h3><p class="mt-1.5 text-sm leading-6 text-slate-300">Apply or connect with context.</p></li>
                        <li class="border-t border-white/20 pt-3"><span class="text-xs font-semibold text-[#9bd2c6]">03</span><h3 class="mt-2 font-semibold">Move forward</h3><p class="mt-1.5 text-sm leading-6 text-slate-300">Keep plans and next steps clear.</p></li>
                    </ol>
                </div>
            </div>
        </section>

        <section id="kerala" class="scroll-mt-10 border-b border-line py-10 sm:py-12" aria-labelledby="kerala-heading">
            <div class="bt-shell flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                <div class="max-w-2xl"><p class="bt-eyebrow">Rooted in Kerala. Open to the world.</p><h2 id="kerala-heading" class="mt-2 text-xl font-semibold tracking-tight sm:text-2xl">Local roots. Global opportunity.</h2><p class="mt-2 text-sm leading-6 text-ink-600">Connecting Kerala’s technology community with careers, independent talent, and teams everywhere.</p></div>
                <a href="{{ route('jobs.index') }}" class="inline-flex min-h-10 shrink-0 items-center gap-2 text-sm font-semibold text-brand-700 hover:underline">Explore opportunities <span aria-hidden="true">→</span></a>
            </div>
        </section>

        <section class="bt-shell py-12 sm:py-16" aria-labelledby="cta-heading">
            <div class="flex flex-col gap-6 rounded-2xl bg-brand-700 px-6 py-8 text-white sm:flex-row sm:items-center sm:justify-between sm:px-9 sm:py-9">
                <div class="max-w-2xl"><p class="text-xs font-semibold uppercase tracking-[.14em] text-white/70">Your next move starts here</p><h2 id="cta-heading" class="mt-2 text-2xl font-semibold leading-tight tracking-[-.035em] sm:text-3xl">Make the connection that moves you forward.</h2></div>
                <div class="flex shrink-0 flex-wrap gap-3"><a href="{{ route('register') }}" class="inline-flex min-h-11 items-center justify-center rounded-md bg-white px-4 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">Create your profile</a><a href="{{ route('register', ['account_type' => 'employer']) }}" class="inline-flex min-h-11 items-center justify-center rounded-md border border-white/40 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/10">Hire talent</a></div>
            </div>
        </section>
    </main>

    <footer class="border-t border-line bg-white">
        <div class="bt-shell flex flex-col gap-6 py-7 sm:flex-row sm:items-center sm:justify-between">
            <div><a href="{{ route('home') }}" class="inline-flex items-center gap-2.5" aria-label="MTalent home"><span class="grid size-8 place-items-center rounded-lg bg-brand-700 text-sm font-semibold text-white">M</span><span class="font-semibold tracking-tight">MTalent</span></a><p class="mt-2 text-xs text-ink-500">© {{ now()->year }} MTalent. Technology careers, talent, and freelance work.</p></div>
            <nav class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-ink-600" aria-label="Footer navigation"><a class="hover:text-brand-700" href="{{ route('jobs.index') }}">Find a job</a><a class="hover:text-brand-700" href="{{ route('freelancers.index') }}">Freelancers</a><a class="hover:text-brand-700" href="{{ route('employer.jobs.index') }}">For employers</a><a class="hover:text-brand-700" href="{{ route('register') }}">Create an account</a></nav>
        </div>
    </footer>
</body>
</html>
