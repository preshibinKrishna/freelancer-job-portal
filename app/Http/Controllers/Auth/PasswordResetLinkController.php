<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\AccountStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        $email = mb_strtolower(trim($request->string('email')->toString()));
        $user = User::query()->where('email', $email)->first();

        if ($user?->status === AccountStatus::Active) {
            Password::sendResetLink(['email' => $email]);
        }

        return back()->with('status', 'password-reset-link-sent');
    }
}
