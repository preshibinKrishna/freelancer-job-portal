<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Domain\Identity\Actions\AuthenticateUser;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuthenticateUser $authenticate): RedirectResponse
    {
        $authenticate->handle($request, $request->string('email')->toString(), $request->string('password')->toString(), $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('identity.session_version', $request->user()->session_version);
        AuditLog::query()->create(['actor_id' => $request->user()->getKey(), 'subject_id' => $request->user()->getKey(), 'event' => 'auth.login']);

        return redirect()->intended(route('profile.edit'));
    }

    public function destroy(): RedirectResponse
    {
        $userId = Auth::id();

        if ($userId !== null) {
            AuditLog::query()->create(['actor_id' => $userId, 'subject_id' => $userId, 'event' => 'auth.logout']);
        }

        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('home');
    }
}
