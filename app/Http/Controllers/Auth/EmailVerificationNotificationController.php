<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class EmailVerificationNotificationController extends Controller
{
    public function store(): RedirectResponse
    {
        if (request()->user()->hasVerifiedEmail()) {
            return redirect()->route('profile.edit');
        }

        request()->user()->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
