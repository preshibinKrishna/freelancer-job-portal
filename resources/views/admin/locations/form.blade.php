<x-layouts.app :title="($location->exists ? 'Edit' : 'Add').' location · M Talent'">
    <main id="main-content" class="mx-auto max-w-3xl px-4 py-10 sm:px-6">
        <a class="text-sm font-medium text-brand-700 hover:underline" href="{{ route('admin.locations.index') }}">← Geographic locations</a>
        <h1 class="mt-5 text-3xl font-semibold text-ink-950">{{ $location->exists ? 'Edit location' : 'Add a location' }}</h1>
        <p class="mt-2 text-sm text-ink-600">Use the hierarchy that matches the location’s administrative structure. A city may belong directly to a country when no state or province is available.</p>

        <form method="POST" action="{{ $location->exists ? route('admin.locations.update', $location) : route('admin.locations.store') }}" class="mt-8 space-y-6 rounded-lg border border-line bg-white p-5 sm:p-8">
            @csrf
            @if ($location->exists) @method('PUT') @endif
            <div>
                <label class="bt-label" for="type">Location type</label>
                <select id="type" name="type" required class="bt-field">
                    @foreach ($types as $type)<option value="{{ $type->value }}" @selected(old('type', $location->type?->value) === $type->value)>{{ $type->label() }}</option>@endforeach
                </select>
                @error('type') <p class="bt-error">{{ $message }}</p> @enderror
            </div>
            <x-input name="name" label="Name" required maxlength="120" :value="old('name', $location->name)" :error="$errors->first('name')" />
            <div>
                <label class="bt-label" for="parent_id">Parent location <span class="font-normal text-ink-500">(not required for a country)</span></label>
                <select id="parent_id" name="parent_id" class="bt-field">
                    <option value="">No parent</option>
                    @foreach ($parents as $parent)
                        <option value="{{ $parent->id }}" @selected((string) old('parent_id', $location->parent_id) === (string) $parent->id)>{{ $parent->type->label() }} · {{ $parent->name }} ({{ $parent->path() }})</option>
                    @endforeach
                </select>
                @error('parent_id') <p class="bt-error">{{ $message }}</p> @enderror
            </div>
            <x-input name="country_code" label="Country code" hint="ISO 3166-1 alpha-2 code, such as IN or SG. Used only for country records." maxlength="2" pattern="[A-Za-z]{2}" :value="old('country_code', $location->country_code)" :error="$errors->first('country_code')" />
            <div class="flex flex-wrap items-center gap-3 border-t border-line pt-5">
                <x-button type="submit">{{ $location->exists ? 'Save changes' : 'Create location' }}</x-button>
                <a class="rounded-md px-4 py-3 text-sm font-semibold text-ink-700 hover:bg-slate-100" href="{{ route('admin.locations.index') }}">Cancel</a>
            </div>
        </form>
        @if ($location->exists)
            <form method="POST" action="{{ route('admin.locations.destroy', $location) }}" class="mt-4 flex justify-end" onsubmit="return confirm('Remove this location? It must not have child locations or linked records.')">
                @csrf @method('DELETE')
                <button class="rounded-md px-3 py-2 text-sm font-semibold text-danger-700 hover:bg-red-50" type="submit">Delete location</button>
            </form>
        @endif
    </main>
</x-layouts.app>
