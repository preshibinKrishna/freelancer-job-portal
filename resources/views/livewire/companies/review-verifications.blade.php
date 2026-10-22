<main id="main-content" class="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:py-14">
    <p class="bt-eyebrow">Platform operations</p><h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink-950">Company verification</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-ink-600">Review each request against its supporting information. Decisions are recorded in the company’s verification history.</p>
    @if (session('status'))<x-alert class="mt-6" tone="success">{{ session('status') }}</x-alert>@endif
    @if ($verifications->isEmpty())
        <x-empty-state class="mt-8" title="No requests waiting" description="New company verification requests will appear here." />
    @else
        <ul class="mt-8 space-y-5">@foreach($verifications as $verification)
            <li wire:key="verification-{{ $verification->public_id }}" class="rounded-xl border border-line bg-white p-5 shadow-card sm:p-7">
                <div class="flex flex-wrap items-start justify-between gap-4"><div class="flex items-center gap-3"><x-company-logo :name="$verification->company->name" :src="$verification->company->logo_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($verification->company->logo_path) : null"/><div><h2 class="font-semibold text-ink-950">{{ $verification->company->name }}</h2><p class="mt-1 text-sm text-ink-600">{{ $verification->company->industry ?? 'Industry not provided' }} · Submitted by {{ $verification->submitter->name }} · {{ $verification->created_at->diffForHumans() }}</p></div></div>
                </div>
                <div class="mt-5 rounded-md bg-slate-50 p-4"><p class="text-xs font-semibold uppercase tracking-wide text-ink-600">Applicant statement</p><p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink-700">{{ $verification->submission_note }}</p></div>
                <label class="bt-label mt-5" for="review-notes-{{ $verification->public_id }}">Internal reviewer notes <span class="font-normal text-ink-500">(optional; visible only to authorized reviewers)</span></label><textarea id="review-notes-{{ $verification->public_id }}" wire:model="reviewerNotes.{{ $verification->public_id }}" maxlength="2000" rows="2" class="bt-field"></textarea>
                <div class="mt-4 flex flex-wrap justify-end gap-3"><button type="button" wire:click="review('{{ $verification->public_id }}', 'rejected')" wire:confirm="Reject this verification request?" class="min-h-11 rounded-md border border-danger-700 px-4 text-sm font-semibold text-danger-700 hover:bg-red-50">Reject</button><x-button type="button" wire:click="review('{{ $verification->public_id }}', 'verified')">Approve verification</x-button></div>
            </li>
        @endforeach</ul>
        <div class="mt-6">{{ $verifications->links() }}</div>
    @endif
</main>
