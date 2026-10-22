<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Identity\Actions\DeleteAccount;
use App\Domain\Identity\Actions\UpdateUserProfile;
use App\Http\Requests\DeleteAccountRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        Gate::authorize('update', request()->user());

        return view('profile.edit', [
            'user' => request()->user()->load('profile.primaryLocation'),
            'locations' => Location::query()->with('parent')->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateProfileRequest $request, UpdateUserProfile $update): RedirectResponse
    {
        Gate::authorize('update', $request->user());
        $update->handle($request->user(), $request->validated());

        return back()->with('status', 'profile-updated');
    }

    public function destroy(DeleteAccountRequest $request, DeleteAccount $deleteAccount): RedirectResponse
    {
        Gate::authorize('delete', $request->user());
        $deleteAccount->handle($request->user(), $request->user());
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('status', 'account-deleted');
    }
}
