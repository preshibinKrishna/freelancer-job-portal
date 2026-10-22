<?php

declare(strict_types=1);

namespace App\Domain\Verifications\Actions;

use App\Enums\VerificationStatus;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class ReviewVerification
{
    public function execute(Verification $request, User $reviewer, VerificationStatus $decision, ?string $notes): Verification
    {
        Gate::forUser($reviewer)->authorize('verification.review');
        if (! in_array($decision, [VerificationStatus::Approved, VerificationStatus::Rejected], true)) {
            throw ValidationException::withMessages(['decision' => 'Choose approve or reject.']);
        }

        return DB::transaction(function () use ($request, $reviewer, $decision, $notes): Verification {
            Gate::forUser($reviewer)->authorize('verification.review');
            $request = Verification::query()->lockForUpdate()->findOrFail($request->id);
            if ($request->status !== VerificationStatus::Pending) {
                throw ValidationException::withMessages(['verification' => 'Only pending requests can be reviewed.']);
            }
            $request->update(['status' => $decision, 'reviewed_by' => $reviewer->id, 'reviewer_notes' => $notes ? trim($notes) : null, 'reviewed_at' => now()]);
            AuditLog::query()->create(['actor_id' => $reviewer->id, 'subject_id' => $request->user_id, 'event' => 'verification.decided', 'metadata' => ['verification_id' => $request->public_id, 'type' => $request->type->value, 'decision' => $decision->value]]);

            return $request->fresh(['user', 'reviewer']) ?? $request;
        });
    }
}
