<?php

declare(strict_types=1);

namespace App\Livewire\Verifications;

use App\Domain\Verifications\Actions\SubmitVerification;
use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class ManageVerifications extends Component
{
    public string $type = 'skill';

    public string $label = '';

    public string $evidence = '';

    public function submit(SubmitVerification $action): void
    {
        $validated = $this->validate([
            'type' => ['required', 'in:'.implode(',', array_column(VerificationType::cases(), 'value'))],
            'label' => ['required', 'string', 'max:160'],
            'evidence' => ['nullable', 'string', 'max:2000'],
        ]);
        $action->execute(Auth::user(), VerificationType::from($validated['type']), $validated['label'], $validated['evidence'] ?: null);
        $this->reset('label', 'evidence');
        session()->flash('status', 'Verification request submitted for review.');
    }

    public function render(): View
    {
        Gate::authorize('view', Auth::user());

        return view('livewire.verifications.manage', [
            'requests' => Auth::user()->verifications()->latest()->get(),
            'statuses' => VerificationStatus::cases(),
        ])->extends('layouts.livewire', ['title' => 'My verifications · M Talent']);
    }
}
