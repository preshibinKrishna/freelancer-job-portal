<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\AccountStatus;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $user = User::query()->find($user->getKey());

            if ($user === null) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors(['email' => 'This account is currently unavailable.']);
            }
        }

        if ($user !== null && ($user->status !== AccountStatus::Active || (int) $request->session()->get('identity.session_version', $user->session_version) !== $user->session_version)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'This account is currently unavailable.']);
        }

        if ($user !== null && ! $request->session()->has('identity.session_version')) {
            $request->session()->put('identity.session_version', $user->session_version);
        }

        return $next($request);
    }
}
