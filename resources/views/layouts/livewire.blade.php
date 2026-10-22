<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'M Talent' }}</title>
    @if(isset($description))<meta name="description" content="{{ $description }}">@endif
    @if(isset($canonical))<link rel="canonical" href="{{ $canonical }}">@endif
    <meta name="robots" content="{{ $robots ?? 'noindex,nofollow' }}">
    @if(isset($canonical))<meta property="og:url" content="{{ $canonical }}">@endif
    <meta property="og:site_name" content="M Talent">
    <meta property="og:type" content="{{ $ogType ?? 'website' }}">
    <meta property="og:title" content="{{ $title ?? 'M Talent' }}">
    @if(isset($description))<meta property="og:description" content="{{ $description }}"><meta name="twitter:description" content="{{ $description }}">@endif
    @if(isset($canonical))<meta name="twitter:card" content="{{ isset($image) ? 'summary_large_image' : 'summary' }}"><meta name="twitter:title" content="{{ $title ?? 'M Talent' }}">@if(isset($image))<meta name="twitter:image" content="{{ $image }}">@endif @endif
    @if(isset($image))<meta property="og:image" content="{{ $image }}">@endif
    @if(isset($previousUrl))<link rel="prev" href="{{ $previousUrl }}">@endif
    @if(isset($nextUrl))<link rel="next" href="{{ $nextUrl }}">@endif
    @if(isset($structuredData))<script type="application/ld+json">{!! json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>@endif
    @vite(['resources/css/app.css'])
    @livewireStyles
</head>
<body class="min-h-screen bg-canvas text-ink-950 antialiased">
    <a class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-ink-950 focus:px-4 focus:py-2 focus:text-white" href="#main-content">Skip to content</a>
    <header class="sticky top-0 z-30 border-b border-line bg-white/95 backdrop-blur">
        <div class="mx-auto flex min-h-16 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2 font-semibold tracking-tight" aria-label="M Talent home">
                <span class="grid size-8 place-items-center rounded-md bg-brand-700 text-white" aria-hidden="true">M</span>
                <span>M <span class="font-normal text-ink-500">Talent</span></span>
            </a>
            @auth
                <nav class="hidden items-center gap-1 lg:flex" aria-label="Primary navigation">
                    <a @class(['rounded-md px-3 py-2 text-sm font-medium transition', 'bg-brand-50 text-brand-800' => request()->routeIs('jobs.index'), 'text-ink-700 hover:bg-slate-50 hover:text-brand-700' => !request()->routeIs('jobs.index')]) href="{{ route('jobs.index') }}" @if(request()->routeIs('jobs.index')) aria-current="page" @endif>Explore jobs</a>
                    <a @class(['rounded-md px-3 py-2 text-sm font-medium transition', 'bg-brand-50 text-brand-800' => request()->routeIs('candidate.applications.*'), 'text-ink-700 hover:bg-slate-50 hover:text-brand-700' => !request()->routeIs('candidate.applications.*')]) href="{{ route('candidate.applications.index') }}" @if(request()->routeIs('candidate.applications.*')) aria-current="page" @endif>Applications</a>
                    <a class="rounded-md px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-slate-50 hover:text-brand-700" href="{{ route('candidate.saved-jobs.index') }}">Saved jobs</a>
                    @if(auth()->user()->hasRole('recruiter', 'super_admin'))<a class="rounded-md px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-slate-50 hover:text-brand-700" href="{{ route('recruiter.dashboard') }}">Recruiter desk</a>@elseif(auth()->user()->hasRole('employer'))<a class="rounded-md px-3 py-2 text-sm font-medium text-ink-700 transition hover:bg-slate-50 hover:text-brand-700" href="{{ route('employer.companies.index') }}">Employer workspace</a>@endif
                </nav>
                <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                    <livewire:notifications.notification-bell />
                    <a class="hidden max-w-40 truncate rounded-md px-2 py-2 text-sm font-medium text-ink-700 hover:bg-slate-50 sm:inline" href="{{ route('profile.edit') }}" aria-label="Account settings for {{ auth()->user()->name }}">{{ auth()->user()->name }}</a>
                    <form class="hidden sm:block" method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-md px-2 py-2 text-sm font-semibold text-ink-600 hover:bg-slate-50" type="submit">Sign out</button></form>
                    <details class="group relative lg:hidden">
                        <summary class="grid size-10 list-none place-items-center rounded-md border border-line text-ink-700 hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700" aria-label="Open account navigation"><span aria-hidden="true">☰</span></summary>
                        <nav class="absolute right-0 top-12 z-40 max-h-[min(75vh,34rem)] w-[min(20rem,calc(100vw-2rem))] overflow-y-auto rounded-xl border border-line bg-white p-2 shadow-float" aria-label="Account navigation">
                            <p class="truncate px-3 py-2 text-xs font-semibold uppercase tracking-wide text-ink-500">{{ auth()->user()->name }}</p>
                            <a class="block rounded-md px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-brand-50" href="{{ route('jobs.index') }}">Explore jobs</a>
                            <a class="block rounded-md px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-brand-50" href="{{ route('candidate.applications.index') }}">Applications</a>
                            <a class="block rounded-md px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-brand-50" href="{{ route('candidate.saved-jobs.index') }}">Saved jobs</a>
                            <a class="block rounded-md px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-brand-50" href="{{ route('messages.index') }}">Messages</a>
                            <a class="block rounded-md px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-brand-50" href="{{ route('candidate.profile.edit') }}">Professional profile</a>
                            @if(auth()->user()->hasRole('freelancer'))<a class="block rounded-md px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-brand-50" href="{{ route('freelancer.profile.edit') }}">Freelancer profile</a>@endif
                            @if(auth()->user()->hasRole('employer'))<a class="block rounded-md px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-brand-50" href="{{ route('employer.companies.index') }}">Employer workspace</a><a class="block rounded-md px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-brand-50" href="{{ route('employer.jobs.index') }}">Manage jobs</a><a class="block rounded-md px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-brand-50" href="{{ route('employer.recruitment-requests.index') }}">Recruitment</a>@endif
                            @if(auth()->user()->hasRole('recruiter', 'super_admin'))<a class="block rounded-md px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-brand-50" href="{{ route('recruiter.dashboard') }}">Recruiter desk</a>@endif
                            <a class="block rounded-md px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-brand-50" href="{{ route('profile.edit') }}">Account settings</a>
                            <form class="border-t border-line pt-2 sm:hidden" method="POST" action="{{ route('logout') }}">@csrf<button class="w-full rounded-md px-3 py-2.5 text-left text-sm font-semibold text-ink-700 hover:bg-slate-50" type="submit">Sign out</button></form>
                        </nav>
                    </details>
                </div>
            @else
                <div class="flex items-center gap-2 sm:gap-3"><a class="rounded-md px-2 py-2 text-sm font-medium text-ink-700 hover:text-brand-700 sm:px-3" href="{{ route('login') }}">Sign in</a><a class="bt-button-primary min-h-10 px-3 sm:px-4" href="{{ route('register') }}">Get started</a></div>
            @endauth
        </div>
        @auth
            <div class="border-t border-line px-4 py-2 lg:hidden">
                <nav class="mx-auto flex max-w-7xl gap-2 overflow-x-auto" aria-label="Quick links">
                    <a class="shrink-0 rounded-full border border-line px-3 py-1.5 text-xs font-medium text-ink-700 hover:border-brand-300 hover:text-brand-700" href="{{ route('jobs.index') }}">Explore jobs</a>
                    <a class="shrink-0 rounded-full border border-line px-3 py-1.5 text-xs font-medium text-ink-700 hover:border-brand-300 hover:text-brand-700" href="{{ route('candidate.applications.index') }}">Applications</a>
                    <a class="shrink-0 rounded-full border border-line px-3 py-1.5 text-xs font-medium text-ink-700 hover:border-brand-300 hover:text-brand-700" href="{{ route('candidate.saved-jobs.index') }}">Saved jobs</a>
                    <a class="shrink-0 rounded-full border border-line px-3 py-1.5 text-xs font-medium text-ink-700 hover:border-brand-300 hover:text-brand-700" href="{{ route('messages.index') }}">Messages</a>
                </nav>
            </div>
        @endauth
    </header>
    <div aria-live="polite" aria-atomic="true" class="sr-only" wire:loading>Loading updated content</div>
    @yield('content')
    <footer class="mt-12 border-t border-line bg-white"><div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-5 text-xs text-ink-500 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8"><a class="font-semibold text-ink-700" href="{{ route('home') }}">M Talent</a><p>Professional work, with more context and trust.</p><a class="font-medium text-brand-700 hover:underline" href="{{ route('jobs.index') }}">Explore public opportunities</a></div></footer>
    @livewireScripts
</body>
</html>
