<main id="main-content" class="min-w-0">
    @if(session('status'))<p role="status" class="mb-4 rounded-lg border border-success-100 bg-success-100 px-4 py-3 text-sm font-medium text-success-700">{{ session('status') }}</p>@endif
    <header class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="bt-eyebrow">Administration</p><h1 class="mt-1 text-2xl font-semibold tracking-tight sm:text-3xl">{{ $definition['label'] }}</h1></div>
        @if($activeSection === 'locations' && auth()->user()->hasPermission('location.manage'))<a class="bt-button-primary w-fit" href="{{ route('admin.locations.index') }}">Manage locations</a>@endif
        @if($activeSection === 'verification' && auth()->user()->hasPermission('verification.review'))<a class="bt-button-primary w-fit" href="{{ route('admin.verifications.index') }}">Review pending requests</a>@endif
    </header>

    @if($activeSection === 'dashboard')
        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Platform overview">
            @foreach($metrics as $label => $value)<article class="rounded-xl border border-line bg-white p-4 shadow-sm"><p class="text-xs font-semibold uppercase tracking-wide text-ink-500">{{ $label }}</p><p class="mt-2 text-3xl font-semibold tabular-nums">{{ number_format($value) }}</p></article>@endforeach
        </section>
        <section class="mt-6 rounded-xl border border-line bg-white p-5 shadow-sm"><div class="flex flex-wrap items-end justify-between gap-3"><div><h2 class="font-semibold">Operations</h2><p class="mt-1 text-sm text-ink-500">Choose a workspace to review records and manage platform operations.</p></div><p class="text-xs text-ink-500">Access is based on your assigned permissions.</p></div><nav class="mt-4 grid gap-2 sm:grid-cols-2 xl:grid-cols-3" aria-label="Admin shortcuts">@foreach($sections as $key => $item)@if($key !== 'dashboard')<a href="{{ route('admin.console', ['section' => $key]) }}" class="flex items-center justify-between rounded-lg border px-3 py-2.5 text-sm font-medium text-ink-800 hover:border-brand-300 hover:bg-brand-50"><span>{{ $item['label'] }}</span><span aria-hidden="true" class="text-ink-400">↗</span></a>@endif @endforeach</nav></section>
        @if(auth()->user()->hasPermission('verification.review') || auth()->user()->hasPermission('company.verify') || auth()->user()->hasPermission('freelancer.review.moderate'))<section class="mt-5 rounded-xl border border-line bg-white p-5"><h2 class="font-semibold">Review queues</h2><div class="mt-3 flex flex-wrap gap-2">@if(auth()->user()->hasPermission('verification.review'))<a class="bt-button-secondary" href="{{ route('admin.verifications.index') }}">Identity & profile verifications</a>@endif @if(auth()->user()->hasPermission('company.verify'))<a class="bt-button-secondary" href="{{ route('admin.company-verifications.index') }}">Company reviews</a>@endif @if(auth()->user()->hasPermission('freelancer.review.moderate'))<a class="bt-button-secondary" href="{{ route('admin.freelancer-reviews.index') }}">Freelancer reviews</a>@endif</div></section>@endif
    @elseif($activeSection === 'content')
        <section class="rounded-xl border border-line bg-white p-6"><h2 class="font-semibold">Content management</h2><p class="mt-2 max-w-2xl text-sm leading-6 text-ink-600">There are no managed content records in this workspace yet. Content administration will appear here when a content model is configured.</p></section>
    @elseif($activeSection === 'settings')
        <section class="rounded-xl border border-line bg-white p-5"><div class="flex flex-wrap items-end justify-between gap-2"><div><h2 class="font-semibold">Access roles</h2><p class="mt-1 text-sm text-ink-500">Role and permission assignments are managed by authorized administrators.</p></div><span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium">Read only</span></div>@include('livewire.admin.console-table')</section>
    @else
        @if($records instanceof \Illuminate\Contracts\Pagination\Paginator && $records->count() > 0 || ($records instanceof \Illuminate\Contracts\Pagination\Paginator && $records->total() > 0))
            <section class="overflow-hidden rounded-xl border border-line bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm text-ink-500">{{ number_format($records->total()) }} records</p><label class="sr-only" for="admin-search">Search {{ strtolower($definition['label']) }}</label><input id="admin-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Search {{ strtolower($definition['label']) }}" class="w-full rounded-md border-line text-sm sm:max-w-xs"></div>
                @include('livewire.admin.console-table')
                <div class="border-t px-4 py-3">{{ $records->links() }}</div>
            </section>
        @else
            <section class="rounded-xl border border-line bg-white p-8 text-center"><h2 class="font-semibold">No {{ strtolower($definition['label']) }} found</h2><p class="mt-2 text-sm text-ink-500">Records will appear here when they are available.</p>@if($activeSection === 'locations' && auth()->user()->hasPermission('location.manage'))<a class="mt-4 inline-flex bt-button-primary" href="{{ route('admin.locations.create') }}">Add location</a>@endif</section>
        @endif
    @endif
</main>
