<?php

declare(strict_types=1);

namespace App\Livewire\Companies;

use App\Models\Company;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Component;

class CompanyDashboard extends Component
{
    public function render(): View
    {
        Gate::authorize('viewAny', Company::class);

        return view('livewire.companies.dashboard', [
            'companies' => auth()->user()->companies()->with(['headquarters', 'itPark'])->orderBy('name')->get(),
        ])->extends('layouts.livewire', ['title' => 'Employer workspace · M Talent']);
    }
}
