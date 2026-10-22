<div class="rounded-xl border border-line bg-white p-5 shadow-card sm:p-6">
    @if(session('status'))<x-alert class="mb-4" tone="success">{{ session('status') }}</x-alert>@endif
    @if($application)
        <div class="flex items-start justify-between gap-4"><div><p class="text-sm text-ink-600">Your application</p><p class="mt-1 font-semibold capitalize text-ink-950">{{ str($application->status->value)->replace('_', ' ') }}</p></div><a class="text-sm font-semibold text-brand-700 hover:underline" href="{{ route('candidate.applications.index') }}">Track application</a></div>
        @if(in_array($application->status, [\App\Enums\JobApplicationStatus::Applied, \App\Enums\JobApplicationStatus::Reviewing, \App\Enums\JobApplicationStatus::Shortlisted, \App\Enums\JobApplicationStatus::Interview, \App\Enums\JobApplicationStatus::Offer], true))<button type="button" wire:click="withdraw" wire:confirm="Withdraw your application? This cannot be undone." class="mt-4 text-sm font-semibold text-danger-700 hover:underline">Withdraw application</button>@endif
    @elseif($canApply)
        <h2 class="text-lg font-semibold text-ink-950">Interested in this role?</h2><p class="mt-1 text-sm leading-6 text-ink-600">Send a short introduction with your application. Your profile identity is shared only with this hiring team.</p>
        <form wire:submit="apply" class="mt-4 space-y-3"><div><label for="coverLetter" class="bt-label">Introduction <span class="font-normal text-ink-500">(optional)</span></label><textarea id="coverLetter" wire:model="coverLetter" maxlength="5000" rows="4" class="bt-field" placeholder="Tell the team what interests you about this opportunity."></textarea>@error('coverLetter')<p class="bt-error">{{ $message }}</p>@enderror</div>@error('application')<x-alert tone="danger">{{ $message }}</x-alert>@enderror<x-button type="submit" class="w-full">Apply for this role</x-button></form>
    @elseif(!$job->acceptsApplications())
        <h2 class="font-semibold text-ink-950">Applications are closed</h2><p class="mt-1 text-sm leading-6 text-ink-600">This team is not accepting new applications for this role.</p>
    @elseif(auth()->guest())
        <h2 class="font-semibold text-ink-950">Make your next move</h2><p class="mt-1 text-sm leading-6 text-ink-600">Sign in to apply or save this opportunity.</p><a href="{{ route('login') }}" class="bt-button-primary mt-4 w-full">Sign in to apply</a>
    @else
        <p class="text-sm leading-6 text-ink-600">A verified candidate account is required to apply.</p>
    @endif
    @if($canSave)<button type="button" wire:click="save" class="mt-4 inline-flex min-h-10 w-full items-center justify-center rounded-md border border-line px-4 text-sm font-semibold text-ink-800 hover:bg-slate-50">{{ $isSaved ? 'Saved · remove from list' : 'Save job' }}</button>@endif
    @auth
        @if(!$canSave && !$application)<button type="button" disabled class="mt-4 w-full rounded-md border border-line px-4 py-2 text-sm font-medium text-ink-500">{{ auth()->user()->hasRole('candidate') ? 'Verify your account to save' : 'Candidate account required' }}</button>@endif
    @endauth
</div>
