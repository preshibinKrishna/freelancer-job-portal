<?php

declare(strict_types=1);

namespace App\Livewire\Companies;

use App\Domain\Companies\Actions\ReviewCompanyVerification as ReviewAction;
use App\Enums\CompanyVerificationStatus;
use App\Models\CompanyVerification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Livewire\Component;

class ReviewCompanyVerifications extends Component
{
    /** @var array<string, string> */
    public array $reviewerNotes = [];

    public function review(string $verificationId, string $decision): void
    {
        $verification = CompanyVerification::query()->where('public_id', $verificationId)->firstOrFail();
        Gate::authorize('reviewVerification', $verification);

        Validator::make([
            'decision' => $decision,
            'notes' => $this->reviewerNotes[$verificationId] ?? '',
        ], [
            'decision' => ['required', 'in:verified,rejected'],
            'notes' => ['nullable', 'string', 'max:2000', ...($decision === CompanyVerificationStatus::Rejected->value ? ['required'] : [])],
        ])->validate();

        app(ReviewAction::class)->decide($verification, auth()->user(), CompanyVerificationStatus::from($decision), $this->reviewerNotes[$verificationId] ?? null);
        unset($this->reviewerNotes[$verificationId]);
        session()->flash('status', 'Verification decision recorded.');
    }

    public function render(): View
    {
        Gate::authorize('company.verify');

        return view('livewire.companies.review-verifications', [
            'verifications' => CompanyVerification::query()->where('status', CompanyVerificationStatus::Pending)
                ->with(['company', 'submitter'])->oldest()->paginate(20),
        ])->extends('layouts.livewire', ['title' => 'Company verification review · M Talent']);
    }
}
