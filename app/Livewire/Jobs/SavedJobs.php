<?php

declare(strict_types=1);

namespace App\Livewire\Jobs;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SavedJobs extends Component
{
    use WithPagination;

    public function render(): View
    {
        $jobs = Auth::user()->savedJobs()->with(['company', 'location', 'itPark', 'skills'])->latest('saved_jobs.created_at')->paginate(12);

        return view('livewire.jobs.saved-jobs', compact('jobs'))
            ->extends('layouts.livewire', ['title' => 'Saved jobs · M Talent']);
    }
}
