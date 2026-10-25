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
    <script type="application/ld+json">{!! json_encode([chr(64).'context' => 'https://schema.org', chr(64).'graph' => [[chr(64).'type' => 'Organization', 'name' => 'MTalent', 'url' => route('home')], [chr(64).'type' => 'WebSite', 'name' => 'MTalent', 'url' => route('home'), 'potentialAction' => [chr(64).'type' => 'SearchAction', 'target' => route('jobs.index').'?q={search_term_string}', 'query-input' => 'required name=search_term_string']]]], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
    <title>MTalent — Technology careers, talent and freelance work</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-ink-950 antialiased">
    <a class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-ink-950 focus:px-4 focus:py-3 focus:text-white" href="#main-content">Skip to content</a>

    <header class="bt-home-header sticky top-0 z-40 border-b border-line/80 bg-white/95 backdrop-blur-xl">
        <div class="bt-shell bt-home-shell flex min-h-[4.5rem] items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="group flex shrink-0 items-center gap-3" aria-label="MTalent home">
                <span class="bt-home-brand-mark grid size-10 place-items-center rounded-xl text-lg font-semibold text-white">M</span>
                <span><span class="block text-[1.1rem] font-semibold leading-tight tracking-[-.04em] text-ink-950">MTalent</span><span class="mt-0.5 hidden text-[9px] font-semibold uppercase tracking-[.16em] text-ink-400 sm:block">Work · Talent · Projects</span></span>
            </a>
            <nav class="bt-home-desktop-nav hidden items-center gap-1 rounded-full border border-line/80 bg-[#f7f9f8] p-1 lg:flex" aria-label="Main navigation">
                <a class="bt-home-nav-link" href="{{ route('jobs.index') }}">Jobs</a>
                <a class="bt-home-nav-link" href="{{ route('freelancers.index') }}">Freelancers</a>
                <a class="bt-home-nav-link" href="{{ route('projects.index') }}">Projects</a>
                <a class="bt-home-nav-link bt-home-nav-employers" href="{{ route('register', ['account_type' => 'employer']) }}">For employers <span aria-hidden="true">↗</span></a>
            </nav>
            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                @auth
                    <a class="hidden rounded-full px-3 py-2 text-sm font-medium text-ink-600 transition hover:bg-slate-100 hover:text-ink-950 sm:inline-flex" href="{{ route('messages.index') }}">Messages</a>
                    <a class="bt-home-header-cta" href="{{ route('profile.edit') }}">My account <span aria-hidden="true">→</span></a>
                @else
                    <a class="rounded-full px-3 py-2 text-sm font-semibold text-ink-600 transition hover:bg-slate-100 hover:text-ink-950" href="{{ route('login') }}">Sign in</a>
                    <a class="bt-home-header-cta" href="{{ route('register') }}"><span class="hidden sm:inline">Create account</span><span class="sm:hidden">Join</span><span aria-hidden="true">↗</span></a>
                @endauth
            </div>
        </div>
        <nav class="bt-home-mobile-nav grid grid-cols-4 gap-1.5 px-3 py-2 lg:hidden" aria-label="Main navigation">
            <a href="{{ route('jobs.index') }}" aria-label="Find work">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="7" width="17" height="13.5" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M8.5 7V5.5A1.5 1.5 0 0 1 10 4h4a1.5 1.5 0 0 1 1.5 1.5V7M3.5 12h17M10 12v1.5h4V12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Jobs</span>
            </a>
            <a href="{{ route('freelancers.index') }}" aria-label="Browse freelancers">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.2" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 19v-1.2A4.8 4.8 0 0 1 8.3 13h1.4a4.8 4.8 0 0 1 4.8 4.8V19H3.5ZM16 5.3a3.2 3.2 0 0 1 0 6.2M17 13.2a4.8 4.8 0 0 1 3.5 4.6V19" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Talent</span>
            </a>
            <a href="{{ route('projects.index') }}" aria-label="Browse projects">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6.5h16M6.5 3.5v6M17.5 3.5v6M5 10h14a1.5 1.5 0 0 1 1.5 1.5V19A1.5 1.5 0 0 1 19 20.5H5A1.5 1.5 0 0 1 3.5 19v-7.5A1.5 1.5 0 0 1 5 10Z" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="m8 15 2.2 2.2L16 12" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                <span>Projects</span>
            </a>
            <a href="{{ route('register', ['account_type' => 'employer']) }}" aria-label="Hire talent">
                <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3.5v17M3.5 12h17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.6"/></svg>
                <span>Hire</span>
            </a>
        </nav>
    </header>

    <main id="main-content">
        <section class="bt-home-hero relative isolate overflow-hidden text-white" aria-labelledby="hero-heading">
            <div class="bt-home-glow bt-home-glow-one" aria-hidden="true"></div>
            <div class="bt-home-glow bt-home-glow-two" aria-hidden="true"></div>
            <div class="bt-shell bt-home-shell relative py-12 sm:py-16 lg:py-20">
                <div class="bt-home-enter relative z-10 lg:col-span-2">
                    <div class="max-w-2xl">
                        <p class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-xs font-semibold tracking-wide text-emerald-100"><span class="size-2 rounded-full bg-lime-300 shadow-[0_0_12px_rgba(190,242,100,.8)]" aria-hidden="true"></span> Technology work, made clear</p>
                        <h1 id="hero-heading" class="mt-5 text-4xl font-semibold leading-[1.04] tracking-[-.05em] sm:text-5xl">What brings you to <span class="text-emerald-300">MTalent?</span></h1>
                    </div>

                    <div class="mt-8 grid gap-4 md:grid-cols-2">
                        <article class="bt-home-path bt-home-path-work">
                            <a href="{{ route('jobs.index') }}" class="bt-home-path-main">
                                <span class="bt-home-path-illustration bt-home-path-illustration-work" aria-hidden="true">
                                    <svg viewBox="0 0 180 150" fill="none"><circle cx="91" cy="74" r="57" fill="#DDF3EB"/><circle cx="140" cy="34" r="10" fill="#B7E6D5"/><path d="M45 103.5c0-17 13.8-30.8 30.8-30.8h29.4c17 0 30.8 13.8 30.8 30.8v8.2H45v-8.2Z" fill="#087C74"/><circle cx="90.5" cy="52" r="21" fill="#F4B896"/><path d="M67 50.5c0-13 10.5-23.5 23.5-23.5S114 37.5 114 50.5v2H67v-2Z" fill="#183B3A"/><rect x="108" y="83" width="37" height="27" rx="7" fill="white" stroke="#C7E5D9" stroke-width="2"/><path d="M117 92h19M117 99h13" stroke="#087C74" stroke-linecap="round" stroke-width="2"/><path d="m126 65 5 5 10-12" stroke="#075A55" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"/></svg>
                                </span>
                                <span class="bt-home-path-copy"><span class="block text-xs font-semibold uppercase tracking-[.13em] text-brand-700">For candidates & freelancers</span><span class="mt-2 block text-2xl font-semibold tracking-[-.035em] text-ink-950 sm:text-[1.7rem]">I’m looking for work</span><span class="mt-2 block max-w-xs text-sm leading-5 text-ink-600">Find a job or your next project.</span></span>
                                <span class="bt-home-path-arrow" aria-hidden="true">↗</span>
                            </a>
                            <div class="bt-home-path-links"><a href="{{ route('jobs.index') }}">Find a job <span aria-hidden="true">→</span></a><a href="{{ route('projects.index') }}">Browse projects <span aria-hidden="true">→</span></a></div>
                        </article>

                        <article class="bt-home-path bt-home-path-hire">
                            <a href="{{ route('freelancers.index') }}" class="bt-home-path-main">
                                <span class="bt-home-path-illustration bt-home-path-illustration-hire" aria-hidden="true">
                                    <svg viewBox="0 0 180 150" fill="none"><circle cx="90" cy="75" r="57" fill="#E6EAFE"/><circle cx="43" cy="42" r="9" fill="#CBD4FB"/><rect x="45" y="90" width="90" height="33" rx="10" fill="#354B9B"/><circle cx="90" cy="59" r="20" fill="#F0B995"/><path d="M68 56c0-12.2 9.8-22 22-22s22 9.8 22 22v3H68v-3Z" fill="#253158"/><path d="M67 91c4.4-9.4 13.5-15 23-15s18.6 5.6 23 15" fill="#8195E4"/><rect x="31" y="82" width="27" height="27" rx="13.5" fill="#F6D2B4"/><path d="M30 81c0-8 6-14 14-14s14 6 14 14v2H30v-2Z" fill="#394771"/><rect x="122" y="79" width="27" height="30" rx="13.5" fill="#C8D0F5"/><path d="M121 78c0-8 6-14 14-14s14 6 14 14v2h-28v-2Z" fill="#394771"/><path d="M73 105h34" stroke="white" stroke-linecap="round" stroke-width="2.5"/><circle cx="133" cy="43" r="12" fill="white"/><path d="M133 37v12M127 43h12" stroke="#354B9B" stroke-linecap="round" stroke-width="2"/></svg>
                                </span>
                                <span class="bt-home-path-copy"><span class="block text-xs font-semibold uppercase tracking-[.13em] text-indigo-700">For employers & recruiters</span><span class="mt-2 block text-2xl font-semibold tracking-[-.035em] text-ink-950 sm:text-[1.7rem]">I’m here to hire</span><span class="mt-2 block max-w-xs text-sm leading-5 text-ink-600">Meet people who can move work forward.</span></span>
                                <span class="bt-home-path-arrow" aria-hidden="true">↗</span>
                            </a>
                            <div class="bt-home-path-links"><a href="{{ route('freelancers.index') }}">Find professionals <span aria-hidden="true">→</span></a><a href="{{ route('register', ['account_type' => 'employer']) }}">Post a role <span aria-hidden="true">→</span></a></div>
                        </article>
                    </div>

                    <form action="{{ route('jobs.index') }}" method="GET" class="bt-home-search mt-5 flex flex-col gap-2 rounded-xl bg-white p-2 sm:flex-row sm:items-center" role="search" aria-label="Search technology jobs">
                        <label class="sr-only" for="homepage-search">Role, skill, or company</label>
                        <div class="relative min-w-0 flex-1">
                            <svg class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-ink-400" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.8" stroke="currentColor" stroke-width="1.7"/><path d="m16 16 4.5 4.5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                            <input id="homepage-search" name="q" type="search" class="bt-field min-h-12 border-0 pl-11 shadow-none focus:ring-0" placeholder="Or search jobs by role, skill, or company" autocomplete="off">
                        </div>
                        <button class="bt-button-primary min-h-12 w-full rounded-lg sm:w-auto sm:px-6" type="submit">Search jobs <span class="ml-2" aria-hidden="true">↗</span></button>
                    </form>
                </div>

            </div>
        </section>

        <section class="bg-[#f6f8f7] py-12 sm:py-16" aria-labelledby="jobs-heading">
            <div class="bt-shell bt-home-shell">
                <div class="bt-home-scene-row bt-home-scene-row-jobs">
                    <div class="bt-home-scene-copy">
                        <p class="bt-home-image-kicker">Technology careers</p>
                        <h2 id="jobs-heading" class="bt-home-image-title">Find a job</h2>
                        <p class="bt-home-scene-detail">Explore roles from technology teams.</p>
                        <a href="{{ route('jobs.index') }}" class="bt-home-scene-link">Browse jobs <span aria-hidden="true">→</span></a>
                    </div>
                    <span class="bt-home-scene bt-home-scene-jobs" aria-hidden="true"><svg viewBox="0 0 440 280" fill="none"><circle cx="336" cy="113" r="110" fill="#9BE8D1" fill-opacity=".13"/><circle cx="336" cy="113" r="82" stroke="#B9F2DF" stroke-opacity=".22"/><path d="M298 13h82M339 0v25" stroke="#B9F2DF" stroke-opacity=".42" stroke-linecap="round"/><rect x="145" y="30" width="248" height="214" rx="18" fill="#F7FCFA"/><rect x="145.8" y="30.8" width="246.4" height="212.4" rx="17.2" stroke="#CDE8DF" stroke-width="1.6"/><path d="M163 52h74" stroke="#8AABA1" stroke-linecap="round" stroke-width="5"/><circle cx="354" cy="52" r="10" fill="#D9F3EA"/><path d="M350 52h8M354 48v8" stroke="#087C74" stroke-linecap="round" stroke-width="2"/><rect x="165" y="77" width="208" height="57" rx="11" fill="#E8F5F0"/><circle cx="193" cy="105" r="15" fill="#F2B795"/><path d="M179 105c0-7.7 6.3-14 14-14s14 6.3 14 14v2h-28v-2Z" fill="#173B3A"/><path d="M181 126c1.8-7.3 6.5-11 12-11s10.2 3.7 12 11" fill="#087C74"/><path d="M222 96h89M222 108h62" stroke="#5B7772" stroke-linecap="round" stroke-width="5"/><rect x="165" y="149" width="208" height="68" rx="11" fill="white" stroke="#E2ECE8" stroke-width="2"/><rect x="179" y="164" width="27" height="27" rx="7" fill="#D7F1E7"/><path d="M186 177h13M192.5 170.5v13" stroke="#087C74" stroke-linecap="round" stroke-width="2.4"/><path d="M220 168h96M220 180h71M179 203h108" stroke="#C2D1CD" stroke-linecap="round" stroke-width="4"/><circle cx="356" cy="194" r="19" fill="#EAF8F1"/><path d="m348 194 6 6 11-13" stroke="#087C74" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"/><rect x="65" y="135" width="91" height="70" rx="13" fill="#FFD38B"/><path d="M91 135v-7c0-9 7-16 16-16h7c9 0 16 7 16 16v7" stroke="#173B3A" stroke-linecap="round" stroke-width="5"/><path d="M65 159h91" stroke="#F2B84D" stroke-width="5"/><circle cx="110" cy="161" r="4" fill="#173B3A"/><circle cx="84" cy="228" r="5" fill="#B9F2DF"/><circle cx="410" cy="90" r="4" fill="#FFD38B"/></svg></span>
                </div>

            @if($jobs->isNotEmpty())
                <div class="mt-7 grid gap-4 md:grid-cols-2">
                    @foreach($jobs as $job)
                        <article class="bt-home-listing group">
                            <div class="flex min-w-0 items-start gap-3.5">
                                <x-company-logo :name="$job->company->name" size="md" :src="$job->company->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($job->company->logo_path) : null"/>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold uppercase tracking-[.1em] text-brand-700">{{ $job->workplace_type->label() }}</p>
                                    <h3 class="mt-1 text-lg font-semibold tracking-tight"><a class="hover:text-brand-700" href="{{ route('jobs.show', $job->slug) }}">{{ $job->title }}</a></h3>
                                    <p class="mt-1 text-sm text-ink-600">{{ $job->company->name }}@if($job->location)<span class="mx-1.5 text-ink-300">·</span>{{ $job->location->name }}@endif</p>
                                </div>
                            </div>
                            <div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-line pt-4">
                                <div class="flex flex-wrap gap-2 text-xs text-ink-600"><span class="rounded-full bg-slate-100 px-2.5 py-1">{{ $job->employment_type->label() }}</span>@if($job->salary_min || $job->salary_max)<span class="rounded-full bg-slate-100 px-2.5 py-1">{{ $job->currency }} {{ $job->salary_min ?? '—' }}–{{ $job->salary_max ?? '—' }}</span>@endif</div>
                                <a href="{{ route('jobs.show', $job->slug) }}" class="inline-flex min-h-9 items-center gap-2 text-sm font-semibold text-brand-700">View role <span class="transition-transform group-hover:translate-x-1" aria-hidden="true">→</span></a>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
            </div>
        </section>

        <section class="border-y border-line bg-white py-12 sm:py-16" aria-labelledby="freelancers-heading">
            <div class="bt-shell bt-home-shell">
                <div class="bt-home-scene-row bt-home-scene-row-reverse bt-home-scene-row-freelancers">
                    <div class="bt-home-scene-copy">
                        <p class="bt-home-image-kicker">Independent professionals</p>
                        <h2 id="freelancers-heading" class="bt-home-image-title">Find a freelancer</h2>
                        <p class="bt-home-scene-detail">Meet experts and explore the work they have done.</p>
                        <a href="{{ route('freelancers.index') }}" class="bt-home-scene-link">Explore profiles <span aria-hidden="true">→</span></a>
                    </div>
                    <span class="bt-home-scene bt-home-scene-freelancers" aria-hidden="true"><svg viewBox="0 0 440 280" fill="none"><circle cx="332" cy="126" r="118" fill="#C6D3FF" fill-opacity=".12"/><circle cx="332" cy="126" r="88" stroke="#D7DEFF" stroke-opacity=".24"/><path d="m349 18 5 13 13 5-13 5-5 13-5-13-13-5 13-5 5-13Z" fill="#F4D188"/><rect x="111" y="37" width="247" height="174" rx="15" fill="#E5E9FA"/><rect x="121" y="47" width="227" height="154" rx="9" fill="#FCFCFF"/><path d="M121 56a9 9 0 0 1 9-9h209a9 9 0 0 1 9 9v15H121V56Z" fill="#D9DFF5"/><circle cx="137" cy="59" r="3" fill="#8090C8"/><circle cx="148" cy="59" r="3" fill="#8090C8"/><circle cx="159" cy="59" r="3" fill="#8090C8"/><rect x="136" y="84" width="75" height="101" rx="8" fill="#EFF1FC"/><circle cx="173.5" cy="116" r="18" fill="#D9A47F"/><path d="M156 115c0-9.7 7.8-17.5 17.5-17.5S191 105.3 191 115v2h-35v-2Z" fill="#283558"/><path d="M153 158c2-14 9.7-21 20.5-21S192 144 194 158" fill="#5269B6"/><path d="M151 169h44M151 176h31" stroke="#C2C9E5" stroke-linecap="round" stroke-width="4"/><rect x="225" y="85" width="106" height="12" rx="6" fill="#D9DFF5"/><rect x="225" y="108" width="82" height="7" rx="3.5" fill="#A6B2E0"/><rect x="225" y="123" width="96" height="7" rx="3.5" fill="#D0D6EE"/><rect x="225" y="143" width="45" height="38" rx="6" fill="#D9F2E9"/><path d="m234 167 10-10 8 6 13-14" stroke="#087C74" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"/><rect x="278" y="143" width="53" height="38" rx="6" fill="#F8EBCF"/><path d="M286 169h10v-9h10v-8h10" stroke="#C58C3A" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"/><path d="M86 217h297l-20 19H107l-21-19Z" fill="#9FAAD8"/><path d="M105 236h260" stroke="#D6DDF7" stroke-linecap="round" stroke-width="4"/><rect x="53" y="82" width="66" height="78" rx="12" fill="#F4D188"/><path d="M72 101h29M72 112h21M72 123h27" stroke="#735C37" stroke-linecap="round" stroke-width="4"/><path d="m82 140 8 8 17-18" stroke="#087C74" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"/><circle cx="392" cy="178" r="13" fill="#E8C5AF"/><path d="m385 176 5 5 10-11" stroke="#394F98" stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"/></svg></span>
                </div>

                @if($freelancers->isNotEmpty())
                    <div class="mt-5 grid gap-x-8 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($freelancers as $freelancer)
                            @php($profile = $freelancer->talentProfile)
                            <article class="bt-home-person group">
                                <div class="flex items-start gap-3">
                                    <div class="grid size-11 shrink-0 place-items-center rounded-full bg-gradient-to-br from-brand-100 to-indigo-100 text-base font-semibold text-brand-700" aria-hidden="true">{{ mb_substr($profile->user->name, 0, 1) }}</div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h3 class="truncate font-semibold tracking-tight"><a href="{{ route('freelancers.show', $profile->profile_slug) }}" class="hover:text-brand-700">{{ $profile->user->name }}</a></h3>
                                            @if($freelancer->verification_status === \App\Enums\FreelancerVerificationStatus::Verified)<span class="text-xs font-semibold text-brand-700" title="Verified by MTalent">Verified</span>@endif
                                        </div>
                                        <p class="mt-1 line-clamp-2 text-sm leading-5 text-ink-600">{{ $profile->headline ?: 'Technology professional' }}</p>
                                    </div>
                                </div>
                                <p class="mt-3 text-xs text-ink-500">{{ $profile->primaryLocation?->name ?? 'Location flexible' }}@if($freelancer->hourly_rate)<span class="mx-1.5">·</span>{{ \Illuminate\Support\Number::currency((float) $freelancer->hourly_rate, $freelancer->currency) }}/hr @endif</p>
                                @if($profile->skills->isNotEmpty())
                                    <ul class="mt-3 flex flex-wrap gap-1.5" aria-label="Skills">@foreach($profile->skills->take(3) as $skill)<li class="rounded-full bg-white px-2.5 py-1 text-[11px] font-medium text-ink-700">{{ $skill->name }}</li>@endforeach</ul>
                                @endif
                                <a href="{{ route('freelancers.show', $profile->profile_slug) }}" class="mt-3 inline-flex min-h-8 items-center gap-2 text-sm font-semibold text-brand-700">View profile <span aria-hidden="true">→</span></a>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

        <section class="bt-home-companies relative overflow-hidden" aria-labelledby="companies-heading">
            <div class="bt-shell bt-home-shell relative py-12 sm:py-16">
                <div class="bt-home-scene-row bt-home-scene-row-employers">
                    <div class="bt-home-scene-copy">
                        <p class="bt-home-image-kicker">For employers & recruiters</p>
                        <h2 id="companies-heading" class="bt-home-image-title">Hire talent</h2>
                        <p class="bt-home-scene-detail">Meet professionals ready to move your work forward.</p>
                        <a href="{{ route('register', ['account_type' => 'employer']) }}" class="bt-home-scene-link">Start hiring <span aria-hidden="true">→</span></a>
                    </div>
                    <span class="bt-home-scene bt-home-scene-employers" aria-hidden="true"><svg viewBox="0 0 440 280" fill="none"><circle cx="335" cy="130" r="112" fill="#B8E7D8" fill-opacity=".12"/><circle cx="335" cy="130" r="83" stroke="#C6F4E5" stroke-opacity=".22"/><path d="M311 34h49M335.5 9v48" stroke="#C6F4E5" stroke-opacity=".35" stroke-linecap="round"/><rect x="125" y="38" width="238" height="150" rx="16" fill="#F9FBFA"/><rect x="126" y="39" width="236" height="148" rx="15" stroke="#D5E4E0" stroke-width="2"/><path d="M149 60h93" stroke="#91A7A1" stroke-linecap="round" stroke-width="5"/><circle cx="336" cy="61" r="8" fill="#D5F0E6"/><rect x="149" y="81" width="191" height="86" rx="10" fill="#EAF4F1"/><path d="M186 130h108M240 106v24" stroke="#9ABAB0" stroke-linecap="round" stroke-width="3"/><path d="M169 129c0-12 9-21 21-21s21 9 21 21v23h-42v-23Z" fill="#087C74"/><circle cx="190" cy="99" r="14" fill="#E2B08E"/><path d="M176 98c0-7.7 6.3-14 14-14s14 6.3 14 14v2h-28v-2Z" fill="#263B43"/><path d="M220 129c0-12 9-21 20-21s21 9 21 21v23h-41v-23Z" fill="#5269B6"/><circle cx="240.5" cy="99" r="14" fill="#B87F5E"/><path d="M226 98c0-7.7 6.3-14 14.5-14S255 90.3 255 98v2h-29v-2Z" fill="#3B3445"/><path d="M270 129c0-12 9-21 20-21s21 9 21 21v23h-41v-23Z" fill="#D99B62"/><circle cx="290.5" cy="99" r="14" fill="#F0C19D"/><path d="M276 98c0-7.7 6.3-14 14.5-14S305 90.3 305 98v2h-29v-2Z" fill="#493D36"/><rect x="87" y="182" width="313" height="16" rx="8" fill="#AEC7BF"/><path d="M144 199v25M341 199v25" stroke="#D7E8E2" stroke-linecap="round" stroke-width="6"/><rect x="200" y="216" width="89" height="18" rx="9" fill="#F7D18B"/><path d="M220 225h48" stroke="#7E6337" stroke-linecap="round" stroke-width="3"/><circle cx="86" cy="88" r="19" fill="#DDF5EC"/><path d="m78 88 6 6 11-13" stroke="#087C74" stroke-linecap="round" stroke-linejoin="round" stroke-width="3"/><circle cx="394" cy="106" r="5" fill="#F7D18B"/><circle cx="75" cy="172" r="4" fill="#DDF5EC"/></svg></span>
                </div>
                @if($companies->isNotEmpty())
                    <div class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($companies as $company)
                            <a href="{{ route('companies.show', $company->slug) }}" class="bt-home-company group">
                                <span class="flex items-start justify-between gap-4">
                                    <x-company-logo :name="$company->name" size="md" :src="$company->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path) : null" class="rounded-xl border-0 bg-brand-50 text-brand-700"/>
                                    <span class="bt-home-company-arrow" aria-hidden="true">↗</span>
                                </span>
                                <span class="mt-5 block truncate text-base font-semibold tracking-tight text-ink-950 group-hover:text-brand-700">{{ $company->name }}</span>
                                <span class="mt-1 block truncate text-sm text-ink-500">{{ $company->industry ?: 'Technology company' }}</span>
                                @if($company->headquarters)<span class="mt-4 inline-flex items-center gap-1.5 text-xs font-medium text-ink-500"><svg class="size-3.5" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M16 8.3c0 4.3-6 9.2-6 9.2S4 12.6 4 8.3a6 6 0 1 1 12 0Z" stroke="currentColor" stroke-width="1.4"/><circle cx="10" cy="8" r="2" stroke="currentColor" stroke-width="1.4"/></svg>{{ $company->headquarters->name }}</span>@endif
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </section>

    </main>

    <footer class="bt-home-footer relative overflow-hidden text-white">
        <div class="bt-home-footer-glow" aria-hidden="true"></div>
        <div class="bt-shell bt-home-shell relative py-12 sm:py-16">
            <div class="grid gap-10 border-b border-white/10 pb-10 md:grid-cols-2 lg:grid-cols-[1.35fr_.8fr_.8fr_.8fr] lg:gap-12 lg:pb-14">
                <div class="max-w-sm">
                    <a href="{{ route('home') }}" class="inline-flex items-center gap-3" aria-label="MTalent home"><span class="bt-home-footer-mark grid size-11 place-items-center rounded-xl text-lg font-semibold text-white">M</span><span class="text-xl font-semibold tracking-[-.04em]">MTalent</span></a>
                    <p class="mt-5 text-sm leading-6 text-slate-300">Technology careers, independent work, and the people who make it happen.</p>
                    <a href="{{ route('register') }}" class="bt-home-footer-cta mt-6">Find your next move <span aria-hidden="true">↗</span></a>
                </div>

                <nav aria-label="Explore MTalent"><h2 class="bt-home-footer-heading">Explore</h2><ul class="mt-4 space-y-3"><li><a class="bt-home-footer-link" href="{{ route('jobs.index') }}">Technology jobs</a></li><li><a class="bt-home-footer-link" href="{{ route('freelancers.index') }}">Freelancers</a></li><li><a class="bt-home-footer-link" href="{{ route('projects.index') }}">Projects</a></li></ul></nav>
                <nav aria-label="For employers"><h2 class="bt-home-footer-heading">For employers</h2><ul class="mt-4 space-y-3"><li><a class="bt-home-footer-link" href="{{ route('register', ['account_type' => 'employer']) }}">Hire talent</a></li><li><a class="bt-home-footer-link" href="{{ route('freelancers.index') }}">Find a freelancer</a></li><li><a class="bt-home-footer-link" href="{{ route('login') }}">Employer sign in</a></li></ul></nav>
                <nav aria-label="Your account"><h2 class="bt-home-footer-heading">Your account</h2><ul class="mt-4 space-y-3"><li><a class="bt-home-footer-link" href="{{ route('register') }}">Create account</a></li><li><a class="bt-home-footer-link" href="{{ route('login') }}">Sign in</a></li></ul></nav>
            </div>
            <div class="flex flex-col gap-3 pt-5 text-xs text-slate-400 sm:flex-row sm:items-center sm:justify-between"><p>© {{ now()->year }} MTalent. All rights reserved.</p><p class="inline-flex items-center gap-2"><span class="size-1.5 rounded-full bg-emerald-300" aria-hidden="true"></span>Work and talent, moving forward.</p></div>
        </div>
    </footer>
</body>
</html>
