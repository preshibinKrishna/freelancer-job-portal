@if($request)
    <main id="main-content" class="min-h-screen bg-slate-50 pb-16"><div class="bt-shell max-w-6xl py-8 sm:py-12">
        <a class="text-sm font-semibold text-brand-700 underline" href="{{ route('employer.companies.index') }}">← Employer workspace</a>
        @if(session('status'))<p role="status" class="mt-5 rounded-lg border border-success-100 bg-success-100 p-4 text-sm text-success-700">{{ session('status') }}</p>@endif
        @php($categoryLabel = $categories[$request->category]['label'] ?? 'Technology solution')
        @php($solution = $categories[$request->category]['types'][$request->solution_type] ?? [])
        <header class="mt-7 max-w-3xl"><p class="bt-eyebrow">{{ $request->company->name }} · Guided discovery</p><h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Recommended professionals</h1><p class="mt-3 text-base leading-7 text-ink-600">Based on your {{ strtolower($categoryLabel) }} requirements: <strong>{{ $solution['label'] ?? 'Selected solution' }}</strong>. These are public profiles ranked by the relevant skills in your brief.</p></header>
        <section class="mt-8 grid gap-5 lg:grid-cols-[minmax(0,1fr)_280px]">
            <div class="space-y-4">
                @forelse($matches as $profile)
                    <article class="rounded-xl border border-line bg-white p-5 sm:p-6"><div class="flex flex-col gap-4 sm:flex-row sm:items-start"><div class="grid size-12 shrink-0 place-items-center rounded-full bg-brand-100 font-semibold text-brand-700">{{ mb_substr($profile->user->name, 0, 1) }}</div><div class="min-w-0 flex-1"><h2 class="text-lg font-semibold"><a class="hover:text-brand-700" href="{{ route('talent.show', $profile->profile_slug) }}">{{ $profile->user->name }}</a></h2><p class="mt-1 text-sm text-ink-600">{{ $profile->headline ?: 'Technology professional' }}</p><p class="mt-1 text-xs text-ink-500">{{ $profile->primaryLocation?->name ?? 'Location not specified' }}@if($profile->primaryLocation?->parent), {{ $profile->primaryLocation->parent->name }}@endif · {{ $profile->experience_years === null ? 'Experience not specified' : $profile->experience_years.' years of experience' }}</p>
                    @if($profile->skills->isNotEmpty())<ul class="mt-3 flex flex-wrap gap-2" aria-label="Professional skills">@foreach($profile->skills->take(8) as $skill)<li class="rounded-md bg-slate-50 px-2.5 py-1 text-xs font-medium text-ink-700">{{ $skill->name }}</li>@endforeach</ul>@endif
                    </div><div class="shrink-0 text-sm font-semibold text-brand-700">{{ $profile->matched_skills_count }} relevant {{ \Illuminate\Support\Str::plural('skill', $profile->matched_skills_count) }}</div></div></article>
                @empty
                    <div class="rounded-xl border border-line bg-white p-7"><h2 class="font-semibold">No public profiles match these skills yet</h2><p class="mt-2 text-sm leading-6 text-ink-600">Try adjusting your selected capabilities or browse the professional directory for related expertise.</p><a class="bt-button-secondary mt-5" href="{{ route('freelancers.index') }}">Browse professionals</a></div>
                @endforelse
                @if($matches?->hasPages())<div>{{ $matches->links() }}</div>@endif
            </div>
            <aside class="h-fit rounded-xl border border-line bg-white p-5"><p class="bt-eyebrow">Your brief</p><h2 class="mt-2 text-lg font-semibold">{{ $solution['label'] ?? 'Technology solution' }}</h2><p class="mt-2 text-sm leading-6 text-ink-600">{{ $solution['summary'] ?? '' }}</p><dl class="mt-4 space-y-3 border-t border-line pt-4">@foreach($selectedFields as $key => $field)@if(isset($request->details[$key]))<div><dt class="text-xs font-medium text-ink-500">{{ $field['label'] }}</dt><dd class="mt-0.5 text-sm font-medium text-ink-800">{{ $field['options'][$request->details[$key]] ?? 'Selected' }}</dd></div>@endif @endforeach</dl></aside>
        </section>
    </div></main>
@else
    <main id="main-content" class="min-h-screen bg-slate-50 pb-16"><div class="bt-shell max-w-4xl py-8 sm:py-12">
        <a class="text-sm font-semibold text-brand-700 underline" href="{{ route('employer.companies.index') }}">← Company workspace</a>
        <p class="bt-eyebrow mt-6">Guided talent discovery</p><h1 class="mt-2 text-3xl font-semibold tracking-tight sm:text-4xl">Describe the solution you need</h1><p class="mt-3 max-w-2xl text-base leading-7 text-ink-600">Choose a solution area and answer a few structured questions. You do not need to write a technical specification. We will use your selections to recommend relevant public professional profiles.</p>
        <form class="mt-7 space-y-6" wire:submit="submit">
            <x-card class="p-5 sm:p-7"><h2 class="text-lg font-semibold">Organisation and solution</h2><div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><label class="bt-label" for="companyId">Organisation</label><select id="companyId" class="bt-field" wire:model="companyId"><option value="">Choose an organisation</option>@foreach($companies as $company)<option value="{{ $company->id }}">{{ $company->name }}</option>@endforeach</select>@error('companyId')<p class="bt-error">{{ $message }}</p>@enderror</div>
                <div><label class="bt-label" for="category">Solution area</label><select id="category" class="bt-field" wire:model.live="category"><option value="">Choose a solution area</option>@foreach($categories as $key => $item)<option value="{{ $key }}">{{ $item['label'] }}</option>@endforeach</select>@error('category')<p class="bt-error">{{ $message }}</p>@enderror</div>
                <div><label class="bt-label" for="solutionType">Solution type</label><select id="solutionType" class="bt-field" wire:model.live="solutionType" @disabled($category === '')><option value="">Choose a solution type</option>@foreach($types as $key => $type)<option value="{{ $key }}">{{ $type['label'] }}</option>@endforeach</select>@error('solutionType')<p class="bt-error">{{ $message }}</p>@enderror</div>
                @if($typeDefinition)<div class="sm:col-span-2 rounded-lg bg-brand-50 p-4 text-sm leading-6 text-ink-700"><strong>{{ $typeDefinition['label'] }}.</strong> {{ $typeDefinition['summary'] }}</div>@endif
            </div></x-card>

            @if($typeDefinition)
                <x-card class="p-5 sm:p-7"><h2 class="text-lg font-semibold">Project requirements</h2><p class="mt-1 text-sm text-ink-500">Select the option that best describes your current needs.</p><div class="mt-5 grid gap-5 sm:grid-cols-2">
                    @foreach($selectedFields as $key => $field)<div><label class="bt-label" for="answer-{{ $key }}">{{ $field['label'] }}</label><select id="answer-{{ $key }}" class="bt-field" wire:model="answers.{{ $key }}"><option value="">Select an option</option>@foreach($field['options'] as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@error('answers.'.$key)<p class="bt-error">{{ $message }}</p>@enderror</div>@endforeach
                </div></x-card>

                <x-card class="p-5 sm:p-7"><fieldset><legend class="text-lg font-semibold">Relevant professional capabilities</legend><p class="mt-1 text-sm leading-6 text-ink-500">We have suggested capabilities for your solution type. Adjust the selection to reflect your priorities.</p><div class="mt-5 grid gap-3 sm:grid-cols-2">@foreach($typeDefinition['skills'] as $skill)<label class="flex min-h-11 items-center gap-3 rounded-lg border border-line px-3 py-2 text-sm text-ink-700"><input type="checkbox" value="{{ $skill }}" class="size-4 rounded border-line text-brand-700 focus:ring-brand-600" wire:model="selectedSkills">{{ $skill }}</label>@endforeach</div>@error('selectedSkills')<p class="bt-error">Select at least one relevant capability.</p>@enderror @error('selectedSkills.*')<p class="bt-error">Choose capabilities from the suggested list.</p>@enderror</fieldset></x-card>
                <div class="flex justify-end"><button class="bt-button-primary" type="submit" wire:loading.attr="disabled"><span wire:loading.remove>Show relevant professionals</span><span wire:loading>Preparing recommendations…</span></button></div>
            @endif
        </form>
    </div></main>
@endif
