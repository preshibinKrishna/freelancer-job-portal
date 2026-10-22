<x-layouts.app title="Geographic locations · M Talent">
    <main id="main-content" class="bt-page mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div><p class="bt-eyebrow">Platform settings</p><h1 class="mt-2 text-3xl font-semibold text-ink-950">Geographic locations</h1><p class="mt-2 text-sm text-ink-600">Manage the shared location hierarchy used across talent and marketplace records.</p></div>
            <a class="bt-button-primary" href="{{ route('admin.locations.create') }}">Add location</a>
        </div>

        @if (session('status')) <x-alert tone="success" class="mt-6">{{ session('status') }}</x-alert> @endif
        @if ($errors->has('location')) <x-alert tone="danger" class="mt-6">{{ $errors->first('location') }}</x-alert> @endif

        <div class="mt-8 overflow-x-auto rounded-lg border border-line bg-white">
            <table class="w-full min-w-[42rem] text-left text-sm">
                <thead class="border-b border-line bg-slate-50 text-xs uppercase tracking-wide text-ink-600"><tr><th class="px-5 py-3">Location</th><th class="px-5 py-3">Type</th><th class="px-5 py-3">Parent</th><th class="px-5 py-3">Public page</th><th class="px-5 py-3"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody class="divide-y divide-line">
                    @forelse ($locations as $location)
                        <tr>
                            <td class="px-5 py-4 font-medium text-ink-950">{{ $location->name }}</td>
                            <td class="px-5 py-4 text-ink-700">{{ $location->type->label() }}</td>
                            <td class="px-5 py-4 text-ink-700">{{ $location->parent?->name ?? '—' }}</td>
                            <td class="px-5 py-4"><a class="text-brand-700 underline-offset-4 hover:underline" href="{{ route('locations.show', ['path' => $location->path()]) }}">View page</a></td>
                            <td class="px-5 py-4 text-right"><a class="font-semibold text-brand-700 hover:underline" href="{{ route('admin.locations.edit', $location) }}">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-ink-600">No locations have been added yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-5">{{ $locations->links() }}</div>
    </main>
</x-layouts.app>
