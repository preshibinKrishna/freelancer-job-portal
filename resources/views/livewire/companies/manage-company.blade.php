<main id="main-content" class="mx-auto max-w-5xl px-4 py-9 sm:px-6 lg:py-12">
    <div class="mb-7 flex flex-wrap items-end justify-between gap-4">
        <div><a class="text-sm font-semibold text-brand-700 hover:underline" href="{{ route('employer.companies.index') }}">← Your companies</a><p class="bt-eyebrow mt-5">Employer workspace</p><h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink-950">{{ $company ? 'Manage company' : 'Create your company profile' }}</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-ink-600">Tell candidates what makes your team a thoughtful place to do meaningful work.</p></div>
        @if($company)<x-company-verification-badge :status="$company->verification_status"/>@endif
    </div>
    @if (session('status'))<x-alert class="mb-6" tone="success">{{ session('status') }}</x-alert>@endif
    @error('company')<x-alert class="mb-6" tone="danger">{{ $message }}</x-alert>@enderror

    <form wire:submit="save" class="space-y-8">
        <section class="rounded-xl border border-line bg-white p-5 shadow-card sm:p-7" aria-labelledby="company-profile-heading">
            <h2 id="company-profile-heading" class="text-lg font-semibold text-ink-950">Company details</h2><p class="mt-1 text-sm text-ink-600">Share a clear, accurate picture of your organization.</p>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2"><x-input name="name" label="Company name" required maxlength="160" wire:model="name" :error="$errors->first('name')"/></div>
                <x-input name="industry" label="Industry" required maxlength="120" placeholder="e.g. Software development" wire:model="industry" :error="$errors->first('industry')"/>
                <div><label class="bt-label" for="companySize">Company size</label><select id="companySize" wire:model="companySize" class="bt-field"><option value="">Choose a range</option>@foreach($sizes as $size)<option value="{{ $size->value }}">{{ $size->label() }}</option>@endforeach</select>@error('companySize')<p class="bt-error">{{ $message }}</p>@enderror</div>
                <x-input name="website" type="url" label="Website" placeholder="https://example.com" wire:model="website" :error="$errors->first('website')"/>
                <x-input name="foundedYear" type="number" label="Founded year" min="1800" max="{{ now()->year }}" wire:model="foundedYear" :error="$errors->first('foundedYear')"/>
                <div class="sm:col-span-2"><label class="bt-label" for="description">Company description</label><textarea id="description" wire:model="description" maxlength="5000" rows="5" class="bt-field" placeholder="What does your company do, and what problems does it solve?"></textarea>@error('description')<p class="bt-error">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="bt-label" for="companyCulture">Company culture</label><textarea id="companyCulture" wire:model="companyCulture" maxlength="3000" rows="4" class="bt-field" placeholder="Describe how your team works together."></textarea>@error('companyCulture')<p class="bt-error">{{ $message }}</p>@enderror</div>
                <x-input name="benefitsInput" label="Benefits" hint="Separate items with commas, for example flexible work, learning budget, health cover." wire:model="benefitsInput" :error="$errors->first('benefitsInput')"/>
                <x-input name="technologyStackInput" label="Technology stack" hint="Separate technologies with commas." wire:model="technologyStackInput" :error="$errors->first('technologyStackInput')"/>
            </div>
        </section>

        <section class="rounded-xl border border-line bg-white p-5 shadow-card sm:p-7" aria-labelledby="company-locations-heading">
            <h2 id="company-locations-heading" class="text-lg font-semibold text-ink-950">Locations</h2><p class="mt-1 text-sm text-ink-600">Use the shared location directory. Add one headquarters and any office locations.</p>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div><label class="bt-label" for="headquartersLocationId">Headquarters</label><select id="headquartersLocationId" wire:model="headquartersLocationId" class="bt-field"><option value="">Choose a location</option>@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}@if($location->parent) · {{ $location->parent->name }}@endif</option>@endforeach</select>@error('headquartersLocationId')<p class="bt-error">{{ $message }}</p>@enderror</div>
                <div><label class="bt-label" for="itParkId">IT park</label><select id="itParkId" wire:model="itParkId" class="bt-field"><option value="">Not based in an IT park</option>@foreach($parks as $park)<option value="{{ $park->id }}">{{ $park->name }}@if($park->parent) · {{ $park->parent->name }}@endif</option>@endforeach</select>@error('itParkId')<p class="bt-error">{{ $message }}</p>@enderror</div>
                <div class="sm:col-span-2"><label class="bt-label" for="officeLocationIds">Other office locations</label><select multiple size="5" id="officeLocationIds" wire:model="officeLocationIds" class="bt-field min-h-32">@foreach($locations as $location)<option value="{{ $location->id }}">{{ $location->name }}@if($location->parent) · {{ $location->parent->name }}@endif</option>@endforeach</select><p class="bt-help">On desktop, use Ctrl or Command to select multiple locations. On mobile, use the native multi-select control.</p>@error('officeLocationIds')<p class="bt-error">{{ $message }}</p>@enderror</div>
            </div>
        </section>

        <section class="rounded-xl border border-line bg-white p-5 shadow-card sm:p-7" aria-labelledby="company-brand-heading">
            <h2 id="company-brand-heading" class="text-lg font-semibold text-ink-950">Brand and social links</h2><p class="mt-1 text-sm text-ink-600">Logos and cover imagery are publicly visible only when the profile is published.</p>
            <div class="mt-6 grid gap-6 sm:grid-cols-2">
                <div><label class="bt-label" for="logo">Company logo</label>@if($logo)<img class="mb-3 size-20 rounded-lg border object-cover" src="{{ $logo->temporaryUrl() }}" alt="Preview of selected company logo">@elseif($company?->logo_path)<img class="mb-3 size-20 rounded-lg border object-cover" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->logo_path) }}" alt="Current company logo">@endif<input id="logo" type="file" accept="image/png,image/jpeg,image/webp" wire:model="logo" class="bt-field file:mr-3 file:rounded file:border-0 file:bg-brand-100 file:px-3 file:py-1 file:font-semibold file:text-brand-700">@error('logo')<p class="bt-error">{{ $message }}</p>@enderror</div>
                <div><label class="bt-label" for="coverImage">Cover image</label>@if($coverImage)<img class="mb-3 h-20 w-full rounded-lg border object-cover" src="{{ $coverImage->temporaryUrl() }}" alt="Preview of selected company cover">@elseif($company?->cover_image_path)<img class="mb-3 h-20 w-full rounded-lg border object-cover" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($company->cover_image_path) }}" alt="Current company cover">@endif<input id="coverImage" type="file" accept="image/png,image/jpeg,image/webp" wire:model="coverImage" class="bt-field file:mr-3 file:rounded file:border-0 file:bg-brand-100 file:px-3 file:py-1 file:font-semibold file:text-brand-700">@error('coverImage')<p class="bt-error">{{ $message }}</p>@enderror</div>
                @foreach(['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'x' => 'X', 'youtube' => 'YouTube', 'instagram' => 'Instagram'] as $key => $label)
                    <x-input :name="'socialLinks.'.$key" :label="$label" type="url" placeholder="https://" wire:model="socialLinks.{{ $key }}" :error="$errors->first('socialLinks.'.$key)"/>
                @endforeach
            </div>
        </section>

        <section class="rounded-xl border border-line bg-white p-5 shadow-card sm:p-7" aria-labelledby="private-company-heading">
            <h2 id="private-company-heading" class="text-lg font-semibold text-ink-950">Private contact details</h2><p class="mt-1 text-sm text-ink-600">These fields are for authorized platform and company workflows and are not shown on the public profile.</p>
            <div class="mt-6 grid gap-5 sm:grid-cols-3"><x-input name="contactName" label="Contact name" wire:model="contactName" :error="$errors->first('contactName')"/><x-input name="contactEmail" type="email" label="Contact email" wire:model="contactEmail" :error="$errors->first('contactEmail')"/><x-input name="contactPhone" type="tel" label="Contact phone" wire:model="contactPhone" :error="$errors->first('contactPhone')"/></div>
        </section>

        <section class="rounded-xl border border-line bg-white p-5 shadow-card sm:p-7" aria-labelledby="publishing-heading">
            <h2 id="publishing-heading" class="text-lg font-semibold text-ink-950">Public profile</h2><label class="mt-4 flex items-start gap-3 text-sm text-ink-700"><input type="checkbox" wire:model="published" class="mt-0.5 size-4 rounded border-line text-brand-700 focus:ring-brand-600"><span><span class="font-semibold text-ink-950">Publish this company profile</span><span class="mt-1 block text-ink-600">Published pages are public and search-engine indexable. Private contact details and verification evidence remain hidden.</span></span></label>@error('published')<p class="bt-error">{{ $message }}</p>@enderror
        </section>

        <div class="sticky bottom-0 z-10 -mx-4 border-t border-line bg-canvas/95 px-4 py-4 backdrop-blur sm:static sm:mx-0 sm:border-0 sm:bg-transparent sm:p-0"><div class="flex flex-wrap items-center justify-end gap-3"><a class="rounded-md px-4 py-3 text-sm font-semibold text-ink-700 hover:bg-slate-100" href="{{ route('employer.companies.index') }}">Cancel</a><x-button type="submit" wire:loading.attr="disabled" wire:target="save">Save company profile</x-button></div></div>
    </form>

    @if($company)
        <section class="mt-10 rounded-xl border border-line bg-white p-5 shadow-card sm:p-7" aria-labelledby="company-team-heading">
            <h2 id="company-team-heading" class="text-lg font-semibold text-ink-950">Company team</h2><p class="mt-1 text-sm text-ink-600">Add existing verified accounts to this company workspace. Team membership is separate from account roles.</p>
            @error('member')<x-alert class="mt-4" tone="danger">{{ $message }}</x-alert>@enderror
            <form wire:submit="addMember" class="mt-5 grid gap-3 sm:grid-cols-[1fr_14rem_auto] sm:items-end"><x-input name="memberEmail" type="email" label="Member account email" required wire:model="memberEmail" :error="$errors->first('memberEmail')"/><div><label class="bt-label" for="memberRole">Company role</label><select id="memberRole" wire:model="memberRole" class="bt-field">@foreach($memberRoles as $role)<option value="{{ $role->value }}">{{ ucfirst($role->value) }}</option>@endforeach</select>@error('memberRole')<p class="bt-error">{{ $message }}</p>@enderror</div><x-button type="submit">Add member</x-button></form>
            <ul class="mt-6 divide-y divide-line border-y border-line">@foreach($company->memberships as $membership)<li class="flex flex-wrap items-center justify-between gap-3 py-4"><div><p class="font-semibold text-ink-950">{{ $membership->user->name }} @if($membership->role === \App\Enums\CompanyMemberRole::Owner)<x-badge tone="brand">Owner</x-badge>@endif</p><p class="mt-1 text-sm text-ink-600">{{ $membership->user->email }} · {{ ucfirst($membership->role->value) }}</p></div>@if($membership->role !== \App\Enums\CompanyMemberRole::Owner)<button type="button" wire:click="removeMember({{ $membership->user_id }})" wire:confirm="Remove this person from the company workspace?" class="rounded-md px-3 py-2 text-sm font-semibold text-danger-700 hover:bg-red-50">Remove</button>@endif</li>@endforeach</ul>
        </section>

        <section class="mt-8 rounded-xl border border-line bg-white p-5 shadow-card sm:p-7" aria-labelledby="company-verification-heading">
            <div class="flex flex-wrap items-center justify-between gap-3"><div><h2 id="company-verification-heading" class="text-lg font-semibold text-ink-950">Company verification</h2><p class="mt-1 text-sm text-ink-600">Submit supporting information for an independent platform review.</p></div><x-company-verification-badge :status="$company->verification_status"/></div>
            @error('verification')<x-alert class="mt-4" tone="danger">{{ $message }}</x-alert>@enderror
            @if($company->verification_status !== \App\Enums\CompanyVerificationStatus::Verified && $company->verification_status !== \App\Enums\CompanyVerificationStatus::Pending)
                <form wire:submit="submitVerification" class="mt-5 space-y-4"><div><label class="bt-label" for="verificationNote">Why should this company be verified?</label><textarea id="verificationNote" wire:model="verificationNote" rows="3" maxlength="2000" required class="bt-field"></textarea>@error('verificationNote')<p class="bt-error">{{ $message }}</p>@enderror<p class="mt-2 text-sm text-ink-500">Document upload will be available once private malware scanning is enabled.</p></div><div class="flex justify-end"><x-button type="submit">Submit for review</x-button></div></form>
            @elseif($company->verification_status === \App\Enums\CompanyVerificationStatus::Pending)<p class="mt-5 rounded-md bg-warning-100/60 p-4 text-sm text-warning-700">Your latest request is being reviewed. We’ll update this status when a decision is recorded.</p>@endif
        </section>
    @endif
</main>
