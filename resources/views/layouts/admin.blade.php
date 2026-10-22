<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'M Talent administration' }}</title><meta name="robots" content="noindex,nofollow">
    @vite(['resources/css/app.css']) @livewireStyles
</head>
<body class="min-h-screen bg-slate-50 text-ink-950">
    <a class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50 focus:rounded-md focus:bg-ink-950 focus:px-4 focus:py-2 focus:text-white" href="#main-content">Skip to content</a>
    <header class="sticky top-0 z-30 border-b border-line bg-white/95 backdrop-blur">
        <div class="mx-auto flex min-h-16 max-w-[1600px] items-center justify-between gap-4 px-4 sm:px-6">
            <a href="{{ route('admin.console') }}" class="flex items-center gap-3 font-semibold"><span class="grid size-8 place-items-center rounded-md bg-brand-700 text-white">M</span><span>M Talent <span class="text-ink-500">/ Admin</span></span></a>
            <div class="flex items-center gap-3"><span class="hidden text-sm text-ink-500 sm:inline">{{ auth()->user()->name }}</span><a href="{{ route('home') }}" class="text-sm font-medium text-brand-700 hover:underline">View site</a><form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-md px-2 py-2 text-sm font-semibold text-ink-600 hover:bg-slate-100" type="submit">Sign out</button></form></div>
        </div>
    </header>
    <div class="mx-auto grid max-w-[1600px] gap-5 px-3 py-4 sm:px-6 lg:grid-cols-[220px_minmax(0,1fr)] lg:gap-7 lg:py-7">
        <aside class="min-w-0 lg:sticky lg:top-20 lg:max-h-[calc(100vh-6rem)] lg:overflow-y-auto" aria-label="Administration sections">
            <p class="mb-2 hidden px-3 text-[11px] font-semibold uppercase tracking-[.14em] text-ink-500 lg:block">Workspace</p>
            <nav class="hidden gap-1 rounded-xl border bg-white p-2 lg:block lg:space-y-0.5">
                @foreach(($adminSections ?? []) as $key => $item)
                    <a href="{{ route('admin.console', $key === 'dashboard' ? [] : ['section' => $key]) }}" @class(['flex shrink-0 items-center rounded-lg px-3 py-2 text-sm font-medium transition', 'bg-brand-50 text-brand-800' => ($activeSection ?? '') === $key, 'text-ink-700 hover:bg-slate-50 hover:text-brand-700' => ($activeSection ?? '') !== $key]) @if(($activeSection ?? '') === $key) aria-current="page" @endif>{{ $item['label'] }}</a>
                @endforeach
            </nav>
            <details class="group rounded-xl border bg-white p-2 lg:hidden">
                <summary class="flex min-h-10 list-none items-center justify-between gap-3 rounded-lg px-3 text-sm font-semibold text-ink-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-700"><span>{{ $adminSections[$activeSection]['label'] ?? 'Admin sections' }}</span><span class="text-xs text-ink-500" aria-hidden="true">Browse sections⌄</span></summary>
                <nav class="mt-2 grid max-h-[55vh] grid-cols-2 gap-1 overflow-y-auto border-t border-line pt-2" aria-label="Administration sections">
                    @foreach(($adminSections ?? []) as $key => $item)
                        <a href="{{ route('admin.console', $key === 'dashboard' ? [] : ['section' => $key]) }}" @class(['rounded-md px-3 py-2.5 text-sm font-medium', 'bg-brand-50 text-brand-800' => ($activeSection ?? '') === $key, 'text-ink-700 hover:bg-slate-50' => ($activeSection ?? '') !== $key]) @if(($activeSection ?? '') === $key) aria-current="page" @endif>{{ $item['label'] }}</a>
                    @endforeach
                </nav>
            </details>
        </aside>
        <section class="min-w-0">@yield('content')</section>
    </div>
    @livewireScripts
</body>
</html>
