<?php

declare(strict_types=1);

namespace App\Domain\Verifications\Actions;

use App\Enums\VerificationStatus;
use App\Enums\VerificationType;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class SubmitVerification
{
    public function execute(User $user, VerificationType $type, string $label, ?string $evidence): Verification
    {
        abort_unless($user->isActive(), 403);
        Gate::forUser($user)->authorize('view', $user);

        return DB::transaction(function () use ($user, $type, $label, $evidence): Verification {
            $existing = Verification::query()->where('user_id', $user->id)->where('type', $type)->where('label', trim($label))->where('status', VerificationStatus::Pending)->exists();
            if ($existing) {
                throw ValidationException::withMessages(['verification' => 'A matching request is already pending.']);
            }
            $verification = Verification::query()->create(['user_id' => $user->id, 'type' => $type, 'label' => trim($label), 'evidence' => $evidence ? trim($evidence) : null, 'status' => VerificationStatus::Pending]);
            AuditLog::query()->create(['actor_id' => $user->id, 'subject_id' => $user->id, 'event' => 'verification.requested', 'metadata' => ['verification_id' => $verification->public_id, 'type' => $type->value]]);

            return $verification;
        });
    }
}
