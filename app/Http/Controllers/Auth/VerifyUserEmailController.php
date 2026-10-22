<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VerifyUserEmailController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        Gate::authorize('update', $user);

        abort_unless(hash_equals(sha1($user->getEmailForVerification()), (string) $request->route('hash')), 403);

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
            AuditLog::query()->create([
                'actor_id' => $user->getKey(),
                'subject_id' => $user->getKey(),
                'event' => 'auth.email_verified',
            ]);
        }

        $destination = $user->hasRole('employer') ? 'employer.companies.create' : 'profile.edit';

        return redirect()->route($destination)->with('status', 'email-verified');
    }
}
