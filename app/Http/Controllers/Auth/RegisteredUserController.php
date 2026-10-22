<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Actions\RegisterUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request, RegisterUser $registerUser): RedirectResponse
    {
        $user = $registerUser->handle($request->safe()->only(['name', 'email', 'password', 'account_type']));
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('identity.session_version', $user->session_version);

        return redirect()->route('verification.notice')->with('status', 'verification-link-sent');
    }
}
