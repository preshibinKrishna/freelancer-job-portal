<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Domain\Verifications\Actions\ReviewVerification;
use App\Enums\VerificationStatus;
use App\Models\Verification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class ReviewVerifications extends Component
{
    /** @var array<string, string> */
    public array $notes = [];

    public function decide(string $publicId, string $decision, ReviewVerification $action): void
    {
        abort_unless(in_array($decision, [VerificationStatus::Approved->value, VerificationStatus::Rejected->value], true), 422);
        $this->validate(['notes.'.$publicId => ['nullable', 'string', 'max:2000']]);
        $request = Verification::query()->where('public_id', $publicId)->where('status', VerificationStatus::Pending)->firstOrFail();
        $action->execute($request, Auth::user(), VerificationStatus::from($decision), $this->notes[$publicId] ?? null);
        unset($this->notes[$publicId]);
        session()->flash('status', 'Verification decision recorded.');
    }

    public function render(): View
    {
        Gate::authorize('verification.review');

        return view('livewire.admin.review-verifications', [
            'requests' => Verification::query()->where('status', VerificationStatus::Pending)->with('user')->oldest()->paginate(20),
        ])->extends('layouts.livewire', ['title' => 'Verification review · M Talent']);
    }
}
