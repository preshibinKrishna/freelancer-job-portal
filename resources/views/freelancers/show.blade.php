<x-layouts.app
    :title="$profile->headline ? $profile->headline.' · '.$user->name.' · M Talent' : $user->name.' · Freelancer portfolio · M Talent'"
    :description="$description"
    :canonical="$canonical"
    :robots="$indexable ? 'index,follow' : 'noindex,follow'"
    ogType="profile"
    :structuredData="$structuredData"
    :image="$indexable && $profile->photo_path ? route('talent.photo', $user->public_id) : null"
    :imageAlt="$user->name"
>
    <main id="main-content" class="min-h-screen bg-slate-50 pb-16">
        <header class="border-b bg-white">
            <div class="bt-shell flex min-h-16 items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold"><span class="grid size-8 place-items-center rounded-md bg-brand-700 text-white">M</span>M Talent</a>
                <a href="{{ route('freelancers.index') }}" class="text-sm font-semibold text-brand-700 hover:underline">Explore independent talent</a>
            </div>
        </header>

        <div class="bt-shell max-w-6xl py-8 sm:py-12">
            <x-seo.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Freelancers', 'url' => route('freelancers.index')], ['label' => $user->name]]" class="mb-5"/>
            <article>
                <header class="overflow-hidden rounded-2xl border bg-white shadow-card">
                    <div class="h-28 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-brand-100 via-slate-100 to-white sm:h-40"></div>
                    <div class="px-5 pb-6 sm:px-9 sm:pb-8">
                        <div class="-mt-12 flex flex-col gap-5 sm:-mt-16 sm:flex-row sm:items-end sm:justify-between">
                            <div class="flex items-end gap-4 sm:gap-5">
                                <div class="grid size-24 shrink-0 place-items-center overflow-hidden rounded-2xl border-4 border-white bg-brand-100 text-3xl font-semibold text-brand-700 shadow-sm sm:size-32">
                                    @if($profile->photo_path)<img class="h-full w-full object-cover" src="{{ route('talent.photo', $user->public_id) }}" alt="Portrait of {{ $user->name }}">@else{{ mb_substr($user->name, 0, 1) }}@endif
                                </div>
                                <div class="min-w-0 pb-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">{{ $user->name }}</h1>
                                        @if($freelancer->verification_status === \App\Enums\FreelancerVerificationStatus::Verified)<x-verification-badge label="M Talent verified freelancer"/>@endif
                                        @foreach($user->verifications as $verification)<x-verification-badge :label="$verification->label.' Verified'"/>@endforeach
                                    </div>
                                    <p class="mt-1 max-w-2xl text-base text-ink-700 sm:text-lg">{{ $profile->headline }}</p>
                                    @if($profile->primaryLocation)<p class="mt-2 text-sm text-ink-500">{{ $profile->primaryLocation->name }}@if($profile->primaryLocation->parent), {{ $profile->primaryLocation->parent->name }}@endif</p>@endif
                                </div>
                            </div>
                            @if($profile->availability && $profile->availability !== 'not_looking')
                                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-success-100 bg-success-100 px-3 py-2 text-sm font-medium text-success-700"><span class="size-2 rounded-full bg-success-700" aria-hidden="true"></span>{{ $profile->availability === 'actively_looking' ? 'Available for new work' : 'Open to the right project' }}</span>
                            @endif
                        </div>

                        <div class="mt-7 grid gap-3 border-t pt-5 sm:grid-cols-2 lg:grid-cols-4">
                            @if($freelancer->hourly_rate)<div><p class="text-xs font-medium uppercase tracking-wide text-ink-500">Hourly rate</p><p class="mt-1 text-lg font-semibold">{{ \Illuminate\Support\Number::currency((float) $freelancer->hourly_rate, $freelancer->currency) }} <span class="text-sm font-normal text-ink-500">/ hour</span></p></div>@endif
                            @if($freelancer->project_rate)<div><p class="text-xs font-medium uppercase tracking-wide text-ink-500">Typical project rate</p><p class="mt-1 text-lg font-semibold">{{ \Illuminate\Support\Number::currency((float) $freelancer->project_rate, $freelancer->currency) }}</p></div>@endif
                            @if($freelancer->weekly_capacity)<div><p class="text-xs font-medium uppercase tracking-wide text-ink-500">Weekly capacity</p><p class="mt-1 text-lg font-semibold">{{ $freelancer->weekly_capacity }} <span class="text-sm font-normal text-ink-500">hours</span></p></div>@endif
                            @if($profile->experience_years !== null)<div><p class="text-xs font-medium uppercase tracking-wide text-ink-500">Experience</p><p class="mt-1 text-lg font-semibold">{{ $profile->experience_years }} <span class="text-sm font-normal text-ink-500">{{ \Illuminate\Support\Str::plural('year', $profile->experience_years) }}</span></p></div>@endif
                        </div>

                        @if($freelancer->categories->isNotEmpty())<div class="mt-5 flex flex-wrap gap-2" aria-label="Project categories">@foreach($freelancer->categories as $category)<span class="rounded-full border bg-slate-50 px-3 py-1 text-xs font-medium text-ink-700">{{ $category->name }}</span>@endforeach</div>@endif
                        <nav aria-label="Professional links" class="mt-5 flex flex-wrap gap-2">
                            @if($profile->linkedin_url)<a class="rounded-md border px-3 py-2 text-sm font-medium text-ink-700 hover:border-brand-600 hover:text-brand-700" href="{{ $profile->linkedin_url }}" rel="me noopener noreferrer" target="_blank">LinkedIn <span class="sr-only">(opens in a new tab)</span></a>@endif
                            @if($profile->github_url)<a class="rounded-md border px-3 py-2 text-sm font-medium text-ink-700 hover:border-brand-600 hover:text-brand-700" href="{{ $profile->github_url }}" rel="me noopener noreferrer" target="_blank">GitHub <span class="sr-only">(opens in a new tab)</span></a>@endif
                            @foreach($profile->portfolioLinks as $link)<a class="rounded-md border px-3 py-2 text-sm font-medium text-ink-700 hover:border-brand-600 hover:text-brand-700" href="{{ $link->url }}" rel="noopener noreferrer" target="_blank">{{ $link->title }} <span class="sr-only">(opens in a new tab)</span></a>@endforeach
                        </nav>
                    </div>
                </header>

                <div class="mt-6 grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <div class="min-w-0">
                        @if($profile->bio)<section class="rounded-xl border bg-white p-5 sm:p-7" aria-labelledby="about-heading"><p class="bt-eyebrow">Approach & expertise</p><h2 id="about-heading" class="mt-2 text-xl font-semibold">About {{ strtok($user->name, ' ') }}</h2><div class="mt-3 max-w-3xl whitespace-pre-line leading-7 text-ink-700">{{ $profile->bio }}</div></section>@endif

                        @if($freelancer->projects->isNotEmpty())<section class="mt-6" aria-labelledby="work-heading"><div class="mb-4"><p class="bt-eyebrow">Selected work</p><h2 id="work-heading" class="mt-2 text-2xl font-semibold tracking-tight">Project portfolio</h2><p class="mt-1 text-sm text-ink-500">Work selected to show craft, contribution, and outcomes.</p></div><div class="grid gap-4">@foreach($freelancer->projects as $project)<x-freelancer-project-card :project="$project"/>@endforeach</div></section>@endif

                        @if($profile->experiences->isNotEmpty())<section class="mt-6 rounded-xl border bg-white p-5 sm:p-7" aria-labelledby="experience-heading"><h2 id="experience-heading" class="text-lg font-semibold">Professional experience</h2><ol class="mt-5 space-y-6">@foreach($profile->experiences->sortByDesc('started_at') as $experience)<li class="relative border-l border-line pl-5"><span class="absolute -left-1.5 top-1 size-3 rounded-full border-2 border-brand-600 bg-white" aria-hidden="true"></span><h3 class="font-semibold">{{ $experience->title }}</h3><p class="mt-1 text-sm text-ink-700">{{ $experience->company }}@if($experience->location) · {{ $experience->location }}@endif</p><p class="mt-1 text-sm text-ink-500">{{ $experience->started_at?->format('M Y') ?? 'Experience' }} — {{ $experience->is_current ? 'Present' : ($experience->ended_at?->format('M Y') ?? 'Present') }}</p>@if($experience->description)<p class="mt-3 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $experience->description }}</p>@endif</li>@endforeach</ol></section>@endif

                        @if($profile->educations->isNotEmpty())<section class="mt-6 rounded-xl border bg-white p-5 sm:p-7" aria-labelledby="education-heading"><h2 id="education-heading" class="text-lg font-semibold">Education</h2><ul class="mt-4 space-y-4">@foreach($profile->educations as $education)<li><h3 class="font-semibold">{{ $education->institution }}</h3><p class="mt-1 text-sm text-ink-700">{{ collect([$education->degree, $education->field_of_study])->filter()->implode(' · ') }}</p>@if($education->started_at || $education->ended_at)<p class="mt-1 text-sm text-ink-500">{{ $education->started_at?->format('Y') ?? '' }}{{ $education->ended_at ? ' — '.$education->ended_at->format('Y') : '' }}</p>@endif</li>@endforeach</ul></section>@endif

                        @if($profile->certifications->isNotEmpty())<section class="mt-6 rounded-xl border bg-white p-5 sm:p-7" aria-labelledby="certifications-heading"><h2 id="certifications-heading" class="text-lg font-semibold">Certifications</h2><ul class="mt-4 grid gap-4 sm:grid-cols-2">@foreach($profile->certifications as $certification)<li class="rounded-lg border p-4"><h3 class="font-semibold">{{ $certification->name }}</h3>@if($certification->issuer)<p class="mt-1 text-sm text-ink-500">{{ $certification->issuer }}</p>@endif@if($certification->credential_url)<a class="mt-2 inline-block text-sm font-medium text-brand-700 underline" href="{{ $certification->credential_url }}" rel="noopener noreferrer" target="_blank">View credential <span class="sr-only">(opens in a new tab)</span></a>@endif</li>@endforeach</ul></section>@endif
                    </div>

                    <aside class="space-y-6">
                        @if($profile->skills->isNotEmpty())<section class="rounded-xl border bg-white p-5" aria-labelledby="skills-heading"><h2 id="skills-heading" class="font-semibold">Technology & skills</h2><ul class="mt-4 flex flex-wrap gap-2">@foreach($profile->skills as $skill)<li class="rounded-md bg-slate-50 px-2.5 py-1.5 text-xs font-medium text-ink-700">{{ $skill->name }}</li>@endforeach</ul></section>@endif
                        @if($profile->languages->isNotEmpty())<section class="rounded-xl border bg-white p-5" aria-labelledby="languages-heading"><h2 id="languages-heading" class="font-semibold">Languages</h2><ul class="mt-4 space-y-2">@foreach($profile->languages as $language)<li class="flex justify-between gap-3 text-sm"><span class="font-medium">{{ $language->language }}</span>@if($language->proficiency)<span class="text-ink-500">{{ $language->proficiency }}</span>@endif</li>@endforeach</ul></section>@endif
                        @if(($freelancer->approved_reviews_count ?? 0) > 0)<section class="rounded-xl border bg-white p-5" aria-labelledby="reviews-heading"><div class="flex items-end justify-between gap-3"><div><h2 id="reviews-heading" class="font-semibold">Client feedback</h2><p class="mt-1 text-sm text-ink-500">Verified project experience</p></div><x-rating :value="$freelancer->approved_reviews_avg_rating" :count="$freelancer->approved_reviews_count"/></div><div class="mt-4 space-y-4">@foreach($freelancer->reviews as $review)<blockquote class="border-t pt-4"><div class="flex items-center justify-between gap-2"><p class="text-sm font-semibold">{{ $review->title ?: 'Project review' }}</p><span class="text-sm font-semibold text-warning-700">{{ $review->rating }}.0 ★</span></div><p class="mt-2 text-sm leading-6 text-ink-700">{{ $review->review }}</p><footer class="mt-2 text-xs text-ink-500">{{ $review->company->name }} · {{ $review->created_at->format('M Y') }}</footer></blockquote>@endforeach</div></section>@endif
                    </aside>
                </div>
                <footer class="mt-8 border-t pt-5 text-sm text-ink-500"><p>Rates are indicative. Engagement terms are agreed directly between the freelancer and client. M Talent verification and client-confirmed work are shown separately.</p></footer>
            </article>
        </div>
    </main>
</x-layouts.app>
