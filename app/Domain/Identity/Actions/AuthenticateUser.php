<?php

declare(strict_types=1);

namespace App\Domain\Identity\Actions;

use App\Enums\AccountStatus;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

final class AuthenticateUser
{
    public function handle(Request $request, string $email, string $password, bool $remember): void
    {
        $key = mb_strtolower($email).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            event(new Lockout($request));

            throw ValidationException::withMessages(['email' => __('auth.throttle', ['seconds' => RateLimiter::availableIn($key)])]);
        }

        if (! Auth::attempt(['email' => $email, 'password' => $password, 'status' => AccountStatus::Active->value], $remember)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        RateLimiter::clear($key);
    }
}
